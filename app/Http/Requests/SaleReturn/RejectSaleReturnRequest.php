<?php

namespace App\Http\Requests\SaleReturn;

use Illuminate\Foundation\Http\FormRequest;

class RejectSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('returns.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Indica el motivo del rechazo.',
            'rejection_reason.max' => 'El motivo no puede superar los 1000 caracteres.',
        ];
    }
}
