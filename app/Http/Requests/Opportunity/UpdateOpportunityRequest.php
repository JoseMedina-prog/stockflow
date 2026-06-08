<?php

namespace App\Http\Requests\Opportunity;

use App\Enums\OpportunityStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('opportunities.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'stage' => ['required', Rule::enum(OpportunityStage::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'probability' => ['required', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la oportunidad es obligatorio.',
            'amount.required' => 'Indica el monto estimado.',
            'probability.min' => 'La probabilidad debe estar entre 0 y 100.',
            'probability.max' => 'La probabilidad debe estar entre 0 y 100.',
        ];
    }
}
