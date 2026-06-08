<?php

namespace App\Http\Requests\Opportunity;

use App\Enums\OpportunityStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvanceOpportunityRequest extends FormRequest
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
            'target_stage' => ['required', Rule::enum(OpportunityStage::class)],
            'lost_reason' => ['required_if:target_stage,closed_lost', 'nullable', 'string', 'max:1000'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_stage.required' => 'Selecciona la etapa de destino.',
            'lost_reason.required_if' => 'Indica el motivo de la pérdida.',
            'probability.min' => 'La probabilidad debe estar entre 0 y 100.',
            'probability.max' => 'La probabilidad debe estar entre 0 y 100.',
        ];
    }
}
