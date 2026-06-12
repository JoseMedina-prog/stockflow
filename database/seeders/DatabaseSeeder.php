<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\FolioGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        DB::transaction(function () {
            $admin = User::factory()->create([
                'name' => 'StockFlow Admin',
                'email' => 'admin@stockflow.test',
            ]);
            $admin->assignRole('admin');

            User::factory()->create([
                'name' => 'Vendedor Demo',
                'email' => 'vendedor@stockflow.test',
            ])->assignRole('vendedor');

            User::factory()->create([
                'name' => 'Comprador Demo',
                'email' => 'comprador@stockflow.test',
            ])->assignRole('comprador');

            $this->call(SupplierSeeder::class);

            $this->call(ChartOfAccountsSeeder::class);

            $categories = Category::factory()->count(5)->create();

            foreach ($categories as $category) {
                Product::factory()
                    ->count(8)
                    ->state(['category_id' => $category->id])
                    ->create();
            }

            Product::factory()
                ->count(3)
                ->lowStock()
                ->state(['category_id' => $categories->random()->id])
                ->create();

            $customers = Customer::factory()->count(10)->create();

            $products = Product::all();
            $ivaTrasladado = \App\Models\Tax::where('code', 'IVAT-16')->first();
            $adminId = $admin->id;
            foreach ($customers->take(6) as $customer) {
                $sale = Sale::create([
                    'folio' => FolioGenerator::nextSaleFolio(),
                    'user_id' => $adminId,
                    'customer_id' => $customer->id,
                    'total' => 0,
                    'paid_amount' => 0,
                    'balance' => 0,
                    'sale_date' => now()->subDays(rand(0, 25)),
                ]);

                $total = 0;
                $taxesTotal = 0;
                $items = $products->random(rand(1, 4));
                foreach ($items as $product) {
                    $qty = rand(1, 3);
                    $subtotal = round($product->price * $qty, 2);
                    $taxAmount = round($subtotal * 0.16, 2);
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'price' => $product->price,
                        'subtotal' => $subtotal,
                        'tax_id' => $ivaTrasladado?->id,
                        'tax_rate' => 0.16,
                        'tax_amount' => $taxAmount,
                    ]);
                    $total += $subtotal;
                    $taxesTotal += $taxAmount;
                }

                $sale->update(['total' => round($total + $taxesTotal, 2), 'balance' => round($total + $taxesTotal, 2)]);
            }

            $this->call(PurchaseSeeder::class);

            $this->call(BusinessSettingsSeeder::class);

            $this->call(SaleReturnSeeder::class);

            $this->call(PaymentSeeder::class);
        });
    }
}

