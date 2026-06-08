<?php

namespace Database\Seeders;

use App\Enums\AccountNormalBalance;
use App\Enums\AccountType;
use App\Enums\TaxType;
use App\Models\Account;
use App\Models\Tax;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = $this->buildPlan();

        foreach ($accounts as $row) {
            Account::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'normal_balance' => $row['normal_balance'],
                    'category' => $row['category'],
                    'sort' => $row['sort'],
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }

        foreach ($accounts as $row) {
            if (! empty($row['parent_code'])) {
                $parent = Account::where('code', $row['parent_code'])->first();
                if ($parent) {
                    Account::where('code', $row['code'])->update(['parent_id' => $parent->id]);
                }
            }
        }

        $this->seedTaxes();
    }

    /**
     * Catalogo base inspirado en el plan contable mexicano.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildPlan(): array
    {
        $now = now();

        return [
            // ===== ACTIVOS =====
            ['code' => '1000', 'name' => 'Activo', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'header', 'sort' => 10, 'created_at' => $now, 'updated_at' => $now],

            ['code' => '1100', 'name' => 'Activo Circulante', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'header', 'parent_code' => '1000', 'sort' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1101', 'name' => 'Caja', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'cash', 'parent_code' => '1100', 'sort' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1102', 'name' => 'Bancos', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'cash', 'parent_code' => '1100', 'sort' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1103', 'name' => 'Clientes', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'receivable', 'parent_code' => '1100', 'sort' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1104', 'name' => 'Cuentas por Cobrar Diversas', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'receivable', 'parent_code' => '1100', 'sort' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1105', 'name' => 'IVA Acreditable', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'tax', 'parent_code' => '1100', 'sort' => 70, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1106', 'name' => 'IEPS Acreditable', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'tax', 'parent_code' => '1100', 'sort' => 80, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1107', 'name' => 'Inventario', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'inventory', 'parent_code' => '1100', 'sort' => 90, 'created_at' => $now, 'updated_at' => $now],

            ['code' => '1200', 'name' => 'Activo No Circulante', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'header', 'parent_code' => '1000', 'sort' => 100, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1201', 'name' => 'Equipo de cómputo', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'fixed', 'parent_code' => '1200', 'sort' => 110, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1202', 'name' => 'Mobiliario y equipo', 'type' => AccountType::Asset->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'fixed', 'parent_code' => '1200', 'sort' => 120, 'created_at' => $now, 'updated_at' => $now],

            // ===== PASIVOS =====
            ['code' => '2000', 'name' => 'Pasivo', 'type' => AccountType::Liability->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'header', 'sort' => 200, 'created_at' => $now, 'updated_at' => $now],

            ['code' => '2100', 'name' => 'Pasivo a Corto Plazo', 'type' => AccountType::Liability->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'header', 'parent_code' => '2000', 'sort' => 210, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2101', 'name' => 'Proveedores', 'type' => AccountType::Liability->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'payable', 'parent_code' => '2100', 'sort' => 220, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2102', 'name' => 'Cuentas por Pagar Diversas', 'type' => AccountType::Liability->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'payable', 'parent_code' => '2100', 'sort' => 230, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2103', 'name' => 'IVA Trasladado', 'type' => AccountType::Liability->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'tax', 'parent_code' => '2100', 'sort' => 240, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2104', 'name' => 'IEPS Trasladado', 'type' => AccountType::Liability->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'tax', 'parent_code' => '2100', 'sort' => 250, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2105', 'name' => 'ISR por Pagar', 'type' => AccountType::Liability->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'tax', 'parent_code' => '2100', 'sort' => 260, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2106', 'name' => 'Notas de Crédito Emitidas', 'type' => AccountType::Liability->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'payable', 'parent_code' => '2100', 'sort' => 270, 'created_at' => $now, 'updated_at' => $now],

            // ===== CAPITAL =====
            ['code' => '3000', 'name' => 'Capital', 'type' => AccountType::Equity->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'header', 'sort' => 300, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '3101', 'name' => 'Capital Social', 'type' => AccountType::Equity->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'equity', 'parent_code' => '3000', 'sort' => 310, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '3102', 'name' => 'Utilidades Acumuladas', 'type' => AccountType::Equity->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'equity', 'parent_code' => '3000', 'sort' => 320, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '3103', 'name' => 'Utilidad del Ejercicio', 'type' => AccountType::Equity->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'equity', 'parent_code' => '3000', 'sort' => 330, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '3104', 'name' => 'Retiro de Utilidades', 'type' => AccountType::Equity->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'equity', 'parent_code' => '3000', 'sort' => 340, 'created_at' => $now, 'updated_at' => $now],

            // ===== INGRESOS =====
            ['code' => '4000', 'name' => 'Ingresos', 'type' => AccountType::Revenue->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'header', 'sort' => 400, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4101', 'name' => 'Ventas', 'type' => AccountType::Revenue->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'sales', 'parent_code' => '4000', 'sort' => 410, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4102', 'name' => 'Devoluciones sobre Ventas', 'type' => AccountType::Revenue->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'sales', 'parent_code' => '4000', 'sort' => 420, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4103', 'name' => 'Descuentos sobre Ventas', 'type' => AccountType::Revenue->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'sales', 'parent_code' => '4000', 'sort' => 430, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4201', 'name' => 'Otros Ingresos', 'type' => AccountType::Revenue->value, 'normal_balance' => AccountNormalBalance::Credit->value, 'category' => 'other_income', 'parent_code' => '4000', 'sort' => 440, 'created_at' => $now, 'updated_at' => $now],

            // ===== EGRESOS =====
            ['code' => '5000', 'name' => 'Egresos', 'type' => AccountType::Expense->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'header', 'sort' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5101', 'name' => 'Costo de Ventas', 'type' => AccountType::Expense->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'cogs', 'parent_code' => '5000', 'sort' => 510, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5102', 'name' => 'Compras', 'type' => AccountType::Expense->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'cogs', 'parent_code' => '5000', 'sort' => 520, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5201', 'name' => 'Gastos de Operación', 'type' => AccountType::Expense->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'opex', 'parent_code' => '5000', 'sort' => 530, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5202', 'name' => 'Gastos de Administración', 'type' => AccountType::Expense->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'opex', 'parent_code' => '5000', 'sort' => 540, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5203', 'name' => 'Gastos de Venta', 'type' => AccountType::Expense->value, 'normal_balance' => AccountNormalBalance::Debit->value, 'category' => 'opex', 'parent_code' => '5000', 'sort' => 550, 'created_at' => $now, 'updated_at' => $now],
        ];
    }

    private function seedTaxes(): void
    {
        $ivaTrasladado = Account::where('code', '2103')->first();
        $ivaAcreditable = Account::where('code', '1105')->first();
        $iepsTrasladado = Account::where('code', '2104')->first();
        $iepsAcreditable = Account::where('code', '1106')->first();
        $isrPorPagar = Account::where('code', '2105')->first();

        $taxes = [
            [
                'code' => 'IVAT-16',
                'name' => 'IVA 16% (Trasladado)',
                'type' => TaxType::IvaTrasladado->value,
                'rate' => 0.16,
                'account_id' => $ivaTrasladado?->id,
            ],
            [
                'code' => 'IVAA-16',
                'name' => 'IVA 16% (Acreditable)',
                'type' => TaxType::IvaAcreditable->value,
                'rate' => 0.16,
                'account_id' => $ivaAcreditable?->id,
            ],
            [
                'code' => 'IVAT-8',
                'name' => 'IVA 8% (Fronterizo)',
                'type' => TaxType::IvaTrasladado->value,
                'rate' => 0.08,
                'account_id' => $ivaTrasladado?->id,
            ],
            [
                'code' => 'IVAT-0',
                'name' => 'IVA 0% (Exento)',
                'type' => TaxType::IvaTrasladado->value,
                'rate' => 0.0,
                'account_id' => $ivaTrasladado?->id,
            ],
            [
                'code' => 'IEPST',
                'name' => 'IEPS 8% (Trasladado)',
                'type' => TaxType::Ieps->value,
                'rate' => 0.08,
                'account_id' => $iepsTrasladado?->id,
            ],
            [
                'code' => 'IEPSA',
                'name' => 'IEPS 8% (Acreditable)',
                'type' => TaxType::Ieps->value,
                'rate' => 0.08,
                'account_id' => $iepsAcreditable?->id,
            ],
            [
                'code' => 'RETISR-10',
                'name' => 'Retención ISR 10%',
                'type' => TaxType::RetencionIsr->value,
                'rate' => 0.10,
                'account_id' => $isrPorPagar?->id,
            ],
        ];

        foreach ($taxes as $row) {
            Tax::updateOrCreate(['code' => $row['code']], array_merge($row, [
                'is_active' => true,
                'is_inclusive' => false,
            ]));
        }
    }
}
