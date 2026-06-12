<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function __construct(private readonly PaymentService $service) {}

    public function run(): void
    {
        $admin = User::where('email', 'admin@stockflow.test')->first()
            ?? User::role('admin')->first()
            ?? User::first();

        if (! $admin) {
            $this->command?->warn('No hay usuarios disponibles para registrar pagos.');

            return;
        }

        DB::transaction(function () use ($admin) {
            $sales = Sale::with('payments')->orderBy('id')->get();

            if ($sales->isEmpty()) {
                $this->command?->warn('No hay ventas para asociar pagos.');

                return;
            }

            $specs = [
                ['method' => PaymentMethod::Cash, 'percent' => 1.0, 'reference' => null, 'days_ago' => 0, 'notes' => 'Pago completo en efectivo al momento de la entrega.'],
                ['method' => PaymentMethod::Card, 'percent' => 1.0, 'reference' => 'AUTH-'.random_int(100000, 999999), 'days_ago' => 1, 'notes' => 'Cobro con tarjeta de débito.'],
                ['method' => PaymentMethod::Transfer, 'percent' => 0.5, 'reference' => 'SPEI-'.random_int(100000, 999999), 'days_ago' => 2, 'notes' => 'Anticipo vía transferencia SPEI.'],
                ['method' => PaymentMethod::Check, 'percent' => 0.3, 'reference' => 'CHQ-'.random_int(10000, 99999), 'days_ago' => 3, 'notes' => 'Cheque a 30 días, primer abono.'],
                ['method' => PaymentMethod::Cash, 'percent' => 0.4, 'reference' => null, 'days_ago' => 1, 'notes' => 'Pago parcial en caja.'],
                ['method' => PaymentMethod::Transfer, 'percent' => 0.75, 'reference' => 'SPEI-'.random_int(100000, 999999), 'days_ago' => 0, 'notes' => 'Liquidación parcial por transferencia.'],
            ];

            foreach ($sales as $index => $sale) {
                $spec = $specs[$index % count($specs)];
                $remaining = (float) $sale->balance;

                if ($remaining <= 0) {
                    continue;
                }

                $amount = round($remaining * $spec['percent'], 2);

                if ($amount <= 0) {
                    continue;
                }

                if ($amount > $remaining) {
                    $amount = $remaining;
                }

                try {
                    $this->service->register(
                        payable: $sale,
                        amount: $amount,
                        method: $spec['method'],
                        user: $admin,
                        paidAt: Carbon::parse($sale->sale_date)->addDays($spec['days_ago']),
                        reference: $spec['reference'],
                        notes: $spec['notes'],
                    );
                } catch (\InvalidArgumentException $e) {
                    $this->command?->warn("Venta #{$sale->id}: {$e->getMessage()}");

                    continue;
                }

                $this->command?->info(sprintf(
                    'Pago registrado en venta %s por $%s (%s).',
                    $sale->folio,
                    number_format($amount, 2),
                    $spec['method']->label(),
                ));
            }

            $purchases = Purchase::with('payments')->orderBy('id')->limit(2)->get();
            $purchaseSpecs = [
                ['method' => PaymentMethod::Transfer, 'percent' => 1.0, 'reference' => 'PROV-'.random_int(100000, 999999), 'days_ago' => 1, 'notes' => 'Pago completo a proveedor.'],
                ['method' => PaymentMethod::Check, 'percent' => 0.5, 'reference' => 'CHQ-'.random_int(10000, 99999), 'days_ago' => 2, 'notes' => 'Anticipo con cheque.'],
            ];

            foreach ($purchases as $index => $purchase) {
                if (! isset($purchaseSpecs[$index])) {
                    continue;
                }

                $spec = $purchaseSpecs[$index];
                $remaining = (float) $purchase->balance;

                if ($remaining <= 0) {
                    continue;
                }

                $amount = round($remaining * $spec['percent'], 2);

                if ($amount <= 0 || $amount > $remaining) {
                    continue;
                }

                try {
                    $this->service->register(
                        payable: $purchase,
                        amount: $amount,
                        method: $spec['method'],
                        user: $admin,
                        paidAt: Carbon::parse($purchase->purchase_date)->addDays($spec['days_ago'])->startOfDay(),
                        reference: $spec['reference'],
                        notes: $spec['notes'],
                    );

                    $this->command?->info(sprintf(
                        'Pago registrado en compra %s por $%s (%s).',
                        $purchase->folio,
                        number_format($amount, 2),
                        $spec['method']->label(),
                    ));
                } catch (\InvalidArgumentException $e) {
                    $this->command?->warn("Compra #{$purchase->id}: {$e->getMessage()}");
                }
            }
        });
    }
}
