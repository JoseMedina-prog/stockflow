<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class BusinessSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'business.name', 'value' => config('app.name'), 'type' => 'string', 'group' => 'business', 'description' => 'Nombre comercial'],
            ['key' => 'business.legal_name', 'value' => '', 'type' => 'string', 'group' => 'business', 'description' => 'Razón social'],
            ['key' => 'business.tax_id', 'value' => '', 'type' => 'string', 'group' => 'business', 'description' => 'RFC'],
            ['key' => 'business.email', 'value' => '', 'type' => 'string', 'group' => 'business', 'description' => 'Correo de contacto'],
            ['key' => 'business.phone', 'value' => '', 'type' => 'string', 'group' => 'business', 'description' => 'Teléfono'],
            ['key' => 'business.address', 'value' => '', 'type' => 'string', 'group' => 'business', 'description' => 'Dirección fiscal'],
            ['key' => 'business.website', 'value' => '', 'type' => 'string', 'group' => 'business', 'description' => 'Sitio web'],
            ['key' => 'business.logo_path', 'value' => '', 'type' => 'string', 'group' => 'business', 'description' => 'Logo'],
            ['key' => 'business.currency', 'value' => 'MXN', 'type' => 'string', 'group' => 'invoicing', 'description' => 'Moneda por defecto'],
            ['key' => 'business.timezone', 'value' => config('app.timezone'), 'type' => 'string', 'group' => 'invoicing', 'description' => 'Zona horaria'],
            ['key' => 'business.invoice_prefix', 'value' => 'V', 'type' => 'string', 'group' => 'invoicing', 'description' => 'Prefijo de folio'],
            ['key' => 'business.return_policy_days', 'value' => '30', 'type' => 'int', 'group' => 'policies', 'description' => 'Días para aceptar devoluciones'],
            ['key' => 'business.notes', 'value' => 'Gracias por su compra.', 'type' => 'string', 'group' => 'invoicing', 'description' => 'Notas predeterminadas en facturas'],
        ];

        foreach ($defaults as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }
    }
}
