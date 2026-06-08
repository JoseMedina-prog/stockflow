<?php

namespace App\Http\Requests\SaleReturn;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('returns.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', 'exists:sale_items,id', 'distinct'],
            'items.*.quantity_returned' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Indica el motivo de la devolución.',
            'reason.max' => 'El motivo no puede superar los 1000 caracteres.',
            'items.required' => 'Agrega al menos un producto a devolver.',
            'items.min' => 'Agrega al menos un producto a devolver.',
            'items.*.sale_item_id.required' => 'Selecciona un producto de la venta.',
            'items.*.sale_item_id.exists' => 'Uno de los productos no existe.',
            'items.*.sale_item_id.distinct' => 'No puedes repetir el mismo producto en la devolución.',
            'items.*.quantity_returned.required' => 'La cantidad es obligatoria.',
            'items.*.quantity_returned.min' => 'La cantidad debe ser al menos 1.',
            'notes.max' => 'Las notas no pueden superar los 2000 caracteres.',
        ];
    }
}
