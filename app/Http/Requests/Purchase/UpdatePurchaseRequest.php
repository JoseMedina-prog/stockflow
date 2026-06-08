<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchases.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Selecciona un proveedor.',
            'purchase_date.required' => 'La fecha de compra es obligatoria.',
            'items.required' => 'Agrega al menos un producto a la compra.',
            'items.min' => 'Agrega al menos un producto a la compra.',
            'items.*.product_id.required' => 'Selecciona un producto.',
            'items.*.product_id.exists' => 'Uno de los productos no existe.',
            'items.*.product_id.distinct' => 'No puedes repetir el mismo producto en la compra.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.unit_cost.required' => 'El costo unitario es obligatorio.',
            'items.*.unit_cost.min' => 'El costo no puede ser negativo.',
            'notes.max' => 'Las notas no pueden superar los 2000 caracteres.',
        ];
    }
}
