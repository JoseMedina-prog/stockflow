<?php

namespace App\Http\Requests\SaleReturn;

use App\Enums\RefundMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveSaleReturnRequest extends FormRequest
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
            'refund_method' => ['required', Rule::enum(RefundMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'refund_method.required' => 'Selecciona el método de reembolso.',
            'notes.max' => 'Las notas no pueden superar los 2000 caracteres.',
        ];
    }
}
