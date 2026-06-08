<?php

namespace App\Http\Controllers;

use App\Enums\TaxType;
use App\Models\Account;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    public function index(Request $request): Response
    {
        $taxes = Tax::with('account:id,code,name')
            ->orderBy('code')
            ->get()
            ->map(fn (Tax $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'type' => $t->type->value,
                'type_label' => $t->type->label(),
                'rate' => (float) $t->rate,
                'percent' => $t->percentRate(),
                'is_active' => $t->is_active,
                'is_inclusive' => $t->is_inclusive,
                'account' => $t->account ? ['code' => $t->account->code, 'name' => $t->account->name] : null,
                'description' => $t->description,
            ]);

        return Inertia::render('Accounting/Taxes', [
            'taxes' => $taxes,
            'types' => array_map(
                fn (TaxType $t) => ['value' => $t->value, 'label' => $t->label()],
                TaxType::cases(),
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTax($request);

        if ($data['account_id'] ?? null) {
            $this->ensureAccountIsTaxCompatible($data['account_id']);
        }

        Tax::create($data);

        return to_route('taxes.index')
            ->with('success', 'Impuesto creado.');
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $data = $this->validateTax($request, $tax);

        if ($data['account_id'] ?? null) {
            $this->ensureAccountIsTaxCompatible($data['account_id']);
        }

        $tax->update($data);

        return to_route('taxes.index')
            ->with('success', "Impuesto {$tax->code} actualizado.");
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        if ($tax->saleItems()->exists() || $tax->purchaseItems()->exists()) {
            throw ValidationException::withMessages([
                'tax' => 'No se puede eliminar: el impuesto ya está siendo utilizado en operaciones.',
            ]);
        }

        $tax->delete();

        return to_route('taxes.index')
            ->with('success', 'Impuesto eliminado.');
    }

    private function validateTax(Request $request, ?Tax $tax = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('taxes', 'code')->ignore($tax?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(TaxType::class)],
            'rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'is_active' => ['boolean'],
            'is_inclusive' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function ensureAccountIsTaxCompatible(int $accountId): void
    {
        $account = Account::find($accountId);
        if (! $account) {
            return;
        }
        if (! in_array($account->type->value, ['asset', 'liability'], true)) {
            throw ValidationException::withMessages([
                'account_id' => 'El impuesto debe asociarse a una cuenta de tipo Activo o Pasivo.',
            ]);
        }
    }
}
