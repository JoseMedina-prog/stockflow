<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StockMovement::query()
            ->with(['product:id,name,sku', 'user:id,name']);

        if ($productId = $request->integer('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($typeValue = $request->string('type')->toString()) {
            $type = StockMovementType::tryFrom($typeValue);
            if ($type !== null) {
                $query->where('type', $type->value);
            }
        }

        if ($from = $request->string('from')->toString()) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to = $request->string('to')->toString()) {
            $query->where('occurred_at', '<=', $to);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        $movements = $query
            ->latest('occurred_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StockMovement $m) => [
                'id' => $m->id,
                'type' => $m->type->value,
                'type_label' => $m->type->label(),
                'type_badge' => $m->type->badgeVariant(),
                'is_out' => $m->type === StockMovementType::Out,
                'quantity' => $m->quantity,
                'signed_quantity' => $m->signedQuantity(),
                'reason' => $m->reason,
                'notes' => $m->notes,
                'occurred_at' => $m->occurred_at->toDateTimeString(),
                'product' => [
                    'id' => $m->product->id,
                    'name' => $m->product->name,
                    'sku' => $m->product->sku,
                ],
                'user' => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name] : null,
                'reference_label' => $this->referenceLabel($m),
                'reference_href' => $this->referenceHref($m),
            ]);

        $products = Product::orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku]);

        return Inertia::render('StockMovements/Index', [
            'movements' => $movements,
            'products' => $products,
            'types' => array_map(
                fn (StockMovementType $t) => ['value' => $t->value, 'label' => $t->label()],
                StockMovementType::cases(),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'product_id' => $request->integer('product_id') ?: null,
                'type' => $request->string('type')->toString() ?: null,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
            ],
        ]);
    }

    private function referenceLabel(StockMovement $movement): ?string
    {
        return match ($movement->reference_type) {
            'App\\Models\\Sale' => 'Venta #'.$movement->reference_id,
            'sale' => 'Venta #'.$movement->reference_id,
            'App\\Models\\Purchase' => 'Compra '.$this->purchaseFolio($movement->reference_id),
            'purchase' => 'Compra '.$this->purchaseFolio($movement->reference_id),
            'App\\Models\\SaleReturn' => 'Devolución '.$this->returnFolio($movement->reference_id),
            default => null,
        };
    }

    private function referenceHref(StockMovement $movement): ?string
    {
        return match ($movement->reference_type) {
            'App\\Models\\Sale', 'sale' => $movement->reference_id ? route('sales.show', $movement->reference_id) : null,
            'App\\Models\\Purchase', 'purchase' => $movement->reference_id ? route('purchases.show', $movement->reference_id) : null,
            'App\\Models\\SaleReturn' => $movement->reference_id ? route('sale-returns.show', $movement->reference_id) : null,
            default => null,
        };
    }

    private function returnFolio(?int $id): string
    {
        if (! $id) {
            return '#?';
        }
        $folio = \App\Models\SaleReturn::find($id)?->folio;

        return $folio ?? '#'.$id;
    }

    private function purchaseFolio(?int $id): string
    {
        if (! $id) {
            return '#?';
        }
        $folio = \App\Models\Purchase::find($id)?->folio;

        return $folio ?? '#'.$id;
    }
}
