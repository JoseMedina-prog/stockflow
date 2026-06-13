<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('quotes.update');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->nullableForeignKey('customers'),
            'opportunity_id' => $this->nullableForeignKey('opportunities'),
        ]);
    }

    /**
     * If the value is empty, zero or non-existent in the given table, return null
     * so that the nullable rule passes and the field is treated as truly optional.
     */
    private function nullableForeignKey(string $table): ?int
    {
        $value = $this->input($table === 'customers' ? 'customer_id' : 'opportunity_id');

        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        if ($id <= 0) {
            return null;
        }

        return \Illuminate\Support\Facades\DB::table($table)->where('id', $id)->exists() ? $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'],
            'quote_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quote_date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.integer' => 'El cliente seleccionado no es válido.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'opportunity_id.integer' => 'La oportunidad seleccionada no es válida.',
            'opportunity_id.exists' => 'La oportunidad seleccionada no existe.',
            'quote_date.required' => 'La fecha de la cotización es obligatoria.',
            'quote_date.date' => 'La fecha de la cotización no es válida.',
            'valid_until.date' => 'La fecha de vigencia no es válida.',
            'valid_until.after_or_equal' => 'La vigencia debe ser igual o posterior a la fecha de cotización.',
            'discount.numeric' => 'El descuento debe ser un número.',
            'discount.min' => 'El descuento no puede ser negativo.',
            'tax.numeric' => 'Los impuestos deben ser un número.',
            'tax.min' => 'Los impuestos no pueden ser negativos.',
            'notes.string' => 'Las notas deben ser texto.',
            'notes.max' => 'Las notas no pueden exceder :max caracteres.',
            'terms.string' => 'Los términos deben ser texto.',
            'terms.max' => 'Los términos no pueden exceder :max caracteres.',
            'items.required' => 'Agrega al menos un concepto a la cotización.',
            'items.min' => 'Agrega al menos un concepto a la cotización.',
            'items.array' => 'Los conceptos no tienen un formato válido.',
            'items.*.product_id.integer' => 'El producto del concepto #:position no es válido.',
            'items.*.product_id.exists' => 'El producto del concepto #:position no existe.',
            'items.*.description.string' => 'La descripción del concepto #:position debe ser texto.',
            'items.*.description.max' => 'La descripción del concepto #:position no puede exceder :max caracteres.',
            'items.*.quantity.required' => 'La cantidad del concepto #:position es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad del concepto #:position debe ser un número entero.',
            'items.*.quantity.min' => 'La cantidad del concepto #:position debe ser al menos 1.',
            'items.*.price.required' => 'El precio del concepto #:position es obligatorio.',
            'items.*.price.numeric' => 'El precio del concepto #:position debe ser un número.',
            'items.*.price.min' => 'El precio del concepto #:position no puede ser negativo.',
            'items.*.discount_percent.numeric' => 'El descuento del concepto #:position debe ser un número.',
            'items.*.discount_percent.min' => 'El descuento del concepto #:position no puede ser negativo.',
            'items.*.discount_percent.max' => 'El descuento del concepto #:position no puede ser mayor a 100.',
        ];
    }
}
