<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\Tax;
use App\Support\SubjectRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountingController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $accountBalances = Account::active()
            ->orderBy('code')
            ->get()
            ->map(function (Account $a) use ($from, $to) {
                return [
                    'id' => $a->id,
                    'code' => $a->code,
                    'name' => $a->name,
                    'type' => $a->type->value,
                    'type_label' => $a->type->label(),
                    'normal_balance' => $a->normal_balance->value,
                    'balance' => $a->balance($from, $to),
                    'parent_id' => $a->parent_id,
                ];
            });

        $summary = [
            'assets' => $this->sumByType($accountBalances, AccountType::Asset),
            'liabilities' => $this->sumByType($accountBalances, AccountType::Liability),
            'equity' => $this->sumByType($accountBalances, AccountType::Equity),
            'revenue' => $this->sumByType($accountBalances, AccountType::Revenue),
            'expense' => $this->sumByType($accountBalances, AccountType::Expense),
        ];

        $summary['net_income'] = round($summary['revenue'] - $summary['expense'], 2);
        $summary['equity_total'] = round($summary['equity'] + $summary['net_income'], 2);
        $summary['balanced'] = abs($summary['assets'] - ($summary['liabilities'] + $summary['equity_total'])) < 0.01;

        $taxes = Tax::active()->with('account')->get();

        $taxSummary = $taxes->map(function (Tax $t) use ($from, $to) {
            $applied = $this->taxMovement($t, $from, $to);
            $balance = $this->taxBalance($t, $from, $to);

            return [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'type' => $t->type->value,
                'type_label' => $t->type->label(),
                'rate' => (float) $t->rate,
                'account' => $t->account ? ['code' => $t->account->code, 'name' => $t->account->name] : null,
                'charged' => $applied['charged'],
                'credited' => $applied['credited'],
                'balance' => $balance,
            ];
        })->values();

        $recentEntries = JournalEntry::with('lines.account')
            ->orderByDesc('entry_date')
            ->limit(10)
            ->get()
            ->map(fn (JournalEntry $e) => [
                'id' => $e->id,
                'folio' => $e->folio,
                'entry_date' => $e->entry_date->toDateString(),
                'concept' => $e->concept,
                'status' => $e->status->value,
                'status_label' => $e->status->label(),
                'total_debit' => (float) $e->total_debit,
                'total_credit' => (float) $e->total_credit,
                'lines_count' => $e->lines->count(),
                'source_href' => $this->sourceHref($e),
            ]);

        return Inertia::render('Accounting/Index', [
            'summary' => $summary,
            'taxSummary' => $taxSummary,
            'recentEntries' => $recentEntries,
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function ledger(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);
        $accountId = $request->integer('account_id') ?: null;

        $query = JournalLine::query()
            ->with(['account', 'journalEntry'])
            ->whereHas('journalEntry', fn ($q) => $q->posted()->inPeriod($from, $to))
            ->orderBy(
                JournalEntry::select('entry_date')
                    ->whereColumn('journal_entries.id', 'journal_lines.journal_entry_id'),
            )
            ->orderBy(
                JournalEntry::select('id')
                    ->whereColumn('journal_entries.id', 'journal_lines.journal_entry_id'),
            )
            ->orderBy('sort');

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $lines = $query->paginate(50)->withQueryString()->through(fn (JournalLine $line) => [
            'id' => $line->id,
            'entry_date' => $line->journalEntry->entry_date->toDateString(),
            'entry_folio' => $line->journalEntry->folio,
            'entry_concept' => $line->journalEntry->concept,
            'account' => [
                'id' => $line->account->id,
                'code' => $line->account->code,
                'name' => $line->account->name,
                'type' => $line->account->type->value,
                'type_label' => $line->account->type->label(),
            ],
            'description' => $line->description,
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
            'source_href' => $this->sourceHref($line->journalEntry),
        ]);

        $accounts = Account::active()->orderBy('code')
            ->get(['id', 'code', 'name', 'type'])
            ->map(fn (Account $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type->value,
                'type_label' => $a->type->label(),
            ]);

        return Inertia::render('Accounting/Ledger', [
            'lines' => $lines,
            'accounts' => $accounts,
            'selectedAccountId' => $accountId,
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $accounts = Account::active()
            ->whereIn('type', [AccountType::Asset->value, AccountType::Liability->value, AccountType::Equity->value])
            ->orderBy('code')
            ->get()
            ->map(function (Account $a) use ($from, $to) {
                $balance = $a->balance($from, $to);
                if ($a->normal_balance->value === 'debit') {
                    return ['account' => $a, 'debit' => $balance, 'credit' => 0];
                }

                return ['account' => $a, 'debit' => 0, 'credit' => $balance];
            });

        $totals = [
            'debit' => $accounts->sum('debit'),
            'credit' => $accounts->sum('credit'),
        ];

        return Inertia::render('Accounting/TrialBalance', [
            'rows' => $accounts->map(fn ($r) => [
                'id' => $r['account']->id,
                'code' => $r['account']->code,
                'name' => $r['account']->name,
                'type_label' => $r['account']->type->label(),
                'debit' => round($r['debit'], 2),
                'credit' => round($r['credit'], 2),
            ])->values(),
            'totals' => $totals,
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function incomeStatement(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $revenue = Account::active()->ofType(AccountType::Revenue)->orderBy('code')->get();
        $expense = Account::active()->ofType(AccountType::Expense)->orderBy('code')->get();

        $balances = Account::balancesFor(
            $revenue->pluck('id')->merge($expense->pluck('id'))->all(),
            $from,
            $to,
        );

        $renderRow = fn (Account $a) => [
            'id' => $a->id, 'code' => $a->code, 'name' => $a->name,
            'amount' => $balances[$a->id] ?? 0.0,
        ];

        $totalRevenue = array_sum(array_column($revenue->map($renderRow)->all(), 'amount'));
        $totalExpense = array_sum(array_column($expense->map($renderRow)->all(), 'amount'));
        $netIncome = round($totalRevenue - $totalExpense, 2);

        return Inertia::render('Accounting/IncomeStatement', [
            'revenue' => $revenue->map($renderRow)->values(),
            'expense' => $expense->map($renderRow)->values(),
            'totals' => [
                'revenue' => round($totalRevenue, 2),
                'expense' => round($totalExpense, 2),
                'net_income' => $netIncome,
            ],
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $assets = Account::active()->ofType(AccountType::Asset)->orderBy('code')->get();
        $liabilities = Account::active()->ofType(AccountType::Liability)->orderBy('code')->get();
        $equity = Account::active()->ofType(AccountType::Equity)->orderBy('code')->get();
        $revenue = Account::active()->ofType(AccountType::Revenue)->get();
        $expense = Account::active()->ofType(AccountType::Expense)->get();

        $balances = Account::balancesFor(
            collect([$assets, $liabilities, $equity, $revenue, $expense])
                ->flatMap(fn ($c) => $c->pluck('id'))
                ->all(),
            $from,
            $to,
        );

        $balancesSum = fn ($collection) => $collection->sum(fn (Account $a) => $balances[$a->id] ?? 0.0);

        $netIncome = $balancesSum($revenue) - $balancesSum($expense);
        $totalAssets = $balancesSum($assets);
        $totalLiabilities = $balancesSum($liabilities);
        $totalEquity = $balancesSum($equity) + $netIncome;

        $renderRow = fn (Account $a) => [
            'id' => $a->id, 'code' => $a->code, 'name' => $a->name,
            'balance' => round($balances[$a->id] ?? 0.0, 2),
        ];

        return Inertia::render('Accounting/BalanceSheet', [
            'assets' => $assets->map($renderRow)->values(),
            'liabilities' => $liabilities->map($renderRow)->values(),
            'equity' => $equity->map($renderRow)->values(),
            'netIncome' => round($netIncome, 2),
            'totals' => [
                'assets' => round($totalAssets, 2),
                'liabilities' => round($totalLiabilities, 2),
                'equity' => round($totalEquity, 2),
                'liabilities_plus_equity' => round($totalLiabilities + $totalEquity, 2),
                'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
            ],
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function taxReport(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $taxes = Tax::active()->with('account')->orderBy('code')->get();

        $rows = $taxes->map(function (Tax $t) use ($from, $to) {
            $movements = $this->taxMovement($t, $from, $to);
            $balance = $this->taxBalance($t, $from, $to);

            return [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'type' => $t->type->value,
                'type_label' => $t->type->label(),
                'rate' => (float) $t->rate,
                'charged' => $movements['charged'],
                'credited' => $movements['credited'],
                'balance' => $balance,
            ];
        })->values();

        return Inertia::render('Accounting/TaxReport', [
            'rows' => $rows,
            'totals' => [
                'charged' => array_sum(array_column($rows->all(), 'charged')),
                'credited' => array_sum(array_column($rows->all(), 'credited')),
                'balance' => array_sum(array_column($rows->all(), 'balance')),
            ],
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function accounts(Request $request): Response
    {
        $accounts = Account::orderBy('code')
            ->with('parent:id,code,name')
            ->get()
            ->map(fn (Account $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type->value,
                'type_label' => $a->type->label(),
                'normal_balance' => $a->normal_balance->value,
                'normal_balance_label' => $a->normal_balance->label(),
                'category' => $a->category,
                'parent' => $a->parent ? ['code' => $a->parent->code, 'name' => $a->parent->name] : null,
                'is_system' => $a->is_system,
                'is_active' => $a->is_active,
                'description' => $a->description,
            ]);

        return Inertia::render('Accounting/Accounts', [
            'accounts' => $accounts,
        ]);
    }

    private function resolvePeriod(Request $request): array
    {
        $from = $request->string('from')->toString() ?: Carbon::now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: Carbon::now()->endOfMonth()->toDateString();

        return [$from, $to];
    }

    private function sumByType($balances, AccountType $type): float
    {
        return round($balances->where('type', $type->value)->sum('balance'), 2);
    }

    private function taxMovement(Tax $tax, ?string $from, ?string $to): array
    {
        $charged = (float) DB::table('sale_items')
            ->where('tax_id', $tax->id)
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->sum('tax_amount');
        $credited = (float) DB::table('purchase_items')
            ->where('tax_id', $tax->id)
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->sum('tax_amount');

        return ['charged' => round($charged, 2), 'credited' => round($credited, 2)];
    }

    private function taxBalance(Tax $tax, ?string $from, ?string $to): float
    {
        if (! $tax->account) {
            return 0.0;
        }

        return round($tax->account->balance($from, $to), 2);
    }

    private function sourceHref(JournalEntry $entry): ?string
    {
        if (! $entry->source_type) {
            return null;
        }

        if ($entry->source_type === Payment::class) {
            return route('payments.index');
        }

        return SubjectRegistry::href($entry->source_type, $entry->source_id);
    }
}
