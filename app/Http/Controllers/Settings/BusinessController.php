<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    private const FIELDS = [
        'name' => ['type' => 'string', 'group' => 'business', 'label' => 'Nombre comercial', 'description' => 'Como aparece en facturas y tickets.'],
        'legal_name' => ['type' => 'string', 'group' => 'business', 'label' => 'Razón social', 'description' => 'Nombre fiscal completo.'],
        'tax_id' => ['type' => 'string', 'group' => 'business', 'label' => 'RFC', 'description' => 'Registro Federal de Contribuyentes.'],
        'email' => ['type' => 'string', 'group' => 'business', 'label' => 'Correo de contacto'],
        'phone' => ['type' => 'string', 'group' => 'business', 'label' => 'Teléfono'],
        'address' => ['type' => 'string', 'group' => 'business', 'label' => 'Dirección fiscal', 'description' => 'Domicilio fiscal completo.'],
        'website' => ['type' => 'string', 'group' => 'business', 'label' => 'Sitio web'],
        'logo_path' => ['type' => 'string', 'group' => 'business', 'label' => 'Logo (ruta)', 'description' => 'Ruta al archivo del logo.'],
        'currency' => ['type' => 'string', 'group' => 'invoicing', 'label' => 'Moneda por defecto', 'description' => 'Código ISO 4217.'],
        'timezone' => ['type' => 'string', 'group' => 'invoicing', 'label' => 'Zona horaria'],
        'invoice_prefix' => ['type' => 'string', 'group' => 'invoicing', 'label' => 'Prefijo de folio de ventas', 'description' => 'Prefijo visible en el folio de cada venta.'],
        'return_policy_days' => ['type' => 'int', 'group' => 'policies', 'label' => 'Días para aceptar devoluciones', 'description' => 'Ventas más antiguas no podrán devolverse.'],
        'notes' => ['type' => 'string', 'group' => 'invoicing', 'label' => 'Notas predeterminadas en facturas', 'description' => 'Pie de factura.'],
    ];

    public function __construct(private readonly SettingService $settings) {}

    public function edit(Request $request): Response
    {
        $values = [];
        foreach (self::FIELDS as $field => $config) {
            $key = "business.{$field}";
            $default = $field === 'name' ? config('app.name') : null;
            $default = $field === 'currency' ? 'MXN' : $default;
            $default = $field === 'timezone' ? config('app.timezone') : $default;
            $default = $field === 'invoice_prefix' ? 'V' : $default;
            $default = $field === 'return_policy_days' ? 30 : $default;

            $values[$field] = $this->settings->get($key, $default);
        }

        return Inertia::render('settings/Business', [
            'business' => $values,
            'fields' => self::FIELDS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::FIELDS as $field => $config) {
            $rules[$field] = $this->ruleFor($field, $config);
        }

        $validated = $request->validate($rules);

        foreach (self::FIELDS as $field => $config) {
            $value = $validated[$field] ?? null;
            $stored = $this->castForStorage($value, $config['type']);
            Setting::updateOrCreate(
                ['key' => "business.{$field}"],
                [
                    'value' => $stored,
                    'type' => $config['type'],
                    'group' => $config['group'],
                    'description' => $config['description'] ?? null,
                ],
            );
        }

        $this->settings->flushCache();

        return to_route('settings.business.edit')
            ->with('success', 'Datos de la empresa actualizados.');
    }

    private function ruleFor(string $field, array $config): array
    {
        return match ($field) {
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'invoice_prefix' => ['nullable', 'string', 'max:5'],
            'return_policy_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:50'],
            default => ['nullable', 'string', 'max:255'],
        };
    }

    private function castForStorage(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int', 'integer' => (string) (int) $value,
            'bool', 'boolean' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }
}
