<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (strlen($q) < 2) {
            return response()->json([
                'products' => [],
                'customers' => [],
                'suppliers' => [],
                'sales' => [],
                'purchases' => [],
                'returns' => [],
                'payments' => [],
                'leads' => [],
                'opportunities' => [],
                'tasks' => [],
                'movements' => [],
            ]);
        }

        $products = Product::query()
            ->with('category:id,name')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'category_id', 'name', 'sku', 'stock', 'price'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->stock,
                'price' => (float) $p->price,
                'category' => $p->category?->name,
                'href' => route('products.edit', $p->id),
            ]);

        $customers = Customer::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email', 'phone'])
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'href' => route('customers.show', $c->id),
            ]);

        $suppliers = Supplier::query()
            ->search($q)
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email', 'phone', 'tax_id'])
            ->map(fn (Supplier $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'phone' => $s->phone,
                'tax_id' => $s->tax_id,
                'href' => route('suppliers.edit', $s->id),
            ]);

        $salesQuery = Sale::with('customer:id,name')
            ->latest('sale_date')
            ->limit(5);

        if (is_numeric($q)) {
            $salesQuery->where('id', (int) $q);
        } else {
            $salesQuery->whereHas('customer', fn ($query) => $query->where('name', 'like', "%{$q}%"));
        }

        $sales = $salesQuery
            ->get(['id', 'customer_id', 'total', 'sale_date'])
            ->map(fn (Sale $s) => [
                'id' => $s->id,
                'folio' => '#'.$s->id,
                'total' => (float) $s->total,
                'sale_date' => $s->sale_date->toDateTimeString(),
                'customer' => $s->customer?->name,
                'href' => route('sales.show', $s->id),
            ]);

        $movements = StockMovement::query()
            ->with('product:id,name,sku')
            ->where(function ($query) use ($q) {
                $query->where('reason', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhereHas('product', fn ($q2) => $q2
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%"));
            })
            ->latest('occurred_at')
            ->limit(5)
            ->get(['id', 'product_id', 'type', 'quantity', 'reason', 'occurred_at'])
            ->map(fn (StockMovement $m) => [
                'id' => $m->id,
                'product' => $m->product?->name,
                'sku' => $m->product?->sku,
                'type' => $m->type->value,
                'type_label' => $m->type->label(),
                'quantity' => $m->quantity,
                'reason' => $m->reason,
                'occurred_at' => $m->occurred_at->toDateTimeString(),
                'href' => route('stock-movements.index', ['search' => $m->product?->sku ?? $m->product?->name]),
            ]);

        $purchases = Purchase::query()
            ->with('supplier:id,name')
            ->where(function ($query) use ($q) {
                $query->where('folio', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($q2) => $q2->where('name', 'like', "%{$q}%"));
            })
            ->latest('purchase_date')
            ->limit(5)
            ->get(['id', 'folio', 'supplier_id', 'total', 'purchase_date', 'status'])
            ->map(fn (Purchase $p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'total' => (float) $p->total,
                'purchase_date' => $p->purchase_date->toDateString(),
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'supplier' => $p->supplier?->name,
                'href' => route('purchases.show', $p->id),
            ]);

        $returns = SaleReturn::query()
            ->with(['customer:id,name', 'sale:id'])
            ->where(function ($query) use ($q) {
                $query->where('folio', 'like', "%{$q}%")
                    ->orWhere('reason', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$q}%"));
            })
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'folio', 'sale_id', 'customer_id', 'total', 'status', 'reason'])
            ->map(fn (SaleReturn $r) => [
                'id' => $r->id,
                'folio' => $r->folio,
                'total' => (float) $r->total,
                'status' => $r->status->value,
                'status_label' => $r->status->label(),
                'reason' => $r->reason,
                'customer' => $r->customer?->name,
                'href' => route('returns.show', $r->id),
            ]);

        $payments = Payment::query()
            ->where(function ($query) use ($q) {
                $query->where('folio', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            })
            ->latest('paid_at')
            ->limit(5)
            ->get(['id', 'folio', 'payable_type', 'payable_id', 'method', 'amount', 'paid_at'])
            ->map(fn (Payment $p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'method' => $p->method->value,
                'method_label' => $p->method->label(),
                'amount' => (float) $p->amount,
                'paid_at' => $p->paid_at->toDateTimeString(),
                'payable_folio' => $p->payable_type === 'App\\Models\\Sale'
                    ? 'V-'.str_pad((string) $p->payable_id, 6, '0', STR_PAD_LEFT)
                    : ($p->payable_type === 'App\\Models\\Purchase' ? 'C-#' : '#').$p->payable_id,
                'payable_href' => $p->payable_type === 'App\\Models\\Sale'
                    ? route('sales.show', $p->payable_id)
                    : ($p->payable_type === 'App\\Models\\Purchase' ? route('purchases.show', $p->payable_id) : null),
            ]);

        $leads = Lead::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'company', 'email', 'stage', 'estimated_value'])
            ->map(fn (Lead $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'company' => $l->company,
                'stage' => $l->stage->value,
                'stage_label' => $l->stage->label(),
                'estimated_value' => (float) ($l->estimated_value ?? 0),
                'href' => route('leads.show', $l->id),
            ]);

        $opportunities = Opportunity::query()
            ->with('customer:id,name')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$q}%"));
            })
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'customer_id', 'amount', 'stage', 'probability'])
            ->map(fn (Opportunity $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'amount' => (float) $o->amount,
                'stage' => $o->stage->value,
                'stage_label' => $o->stage->label(),
                'probability' => (int) $o->probability,
                'customer' => $o->customer?->name,
                'href' => route('opportunities.show', $o->id),
            ]);

        $tasks = Task::query()
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            })
            ->orderBy('due_date')
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'due_date'])
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'priority' => $t->priority->value,
                'priority_label' => $t->priority->label(),
                'due_date' => $t->due_date?->toDateString(),
                'href' => route('tasks.show', $t->id),
            ]);

        return response()->json([
            'products' => $products,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'sales' => $sales,
            'purchases' => $purchases,
            'returns' => $returns,
            'payments' => $payments,
            'leads' => $leads,
            'opportunities' => $opportunities,
            'tasks' => $tasks,
            'movements' => $movements,
        ]);
    }
}
