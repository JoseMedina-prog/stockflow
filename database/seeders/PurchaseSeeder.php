<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(PurchaseService::class);
        $admin = User::where('email', 'admin@stockflow.test')->first();
        $suppliers = Supplier::query()->take(5)->get();
        $products = Product::all();

        if ($suppliers->isEmpty() || $products->isEmpty() || ! $admin) {
            return;
        }

        for ($i = 0; $i < 4; $i++) {
            $supplier = $suppliers->random();
            $items = $products->random(rand(1, 3))->map(fn (Product $p) => [
                'product_id' => $p->id,
                'quantity' => rand(5, 20),
                'unit_cost' => round($p->price * 0.6, 2),
            ])->all();

            $service->create(
                supplier: $supplier,
                user: $admin,
                purchaseDate: now()->subDays(rand(0, 25)),
                items: $items,
                receiveImmediately: true,
            );
        }

        $service->create(
            supplier: $suppliers->random(),
            user: $admin,
            purchaseDate: now()->subDay(),
            items: [
                ['product_id' => $products->random()->id, 'quantity' => 10, 'unit_cost' => 50],
            ],
            receiveImmediately: false,
        );
    }
}
