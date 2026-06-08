<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadSource;
use App\Enums\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leads.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::enum(LeadSource::class)],
            'stage' => ['required', Rule::enum(LeadStage::class)],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.email' => 'El correo no tiene un formato válido.',
            'phone.max' => 'El teléfono no puede superar los 30 caracteres.',
            'company.max' => 'La empresa no puede superar los 255 caracteres.',
            'source.required' => 'Selecciona el origen del lead.',
            'stage.required' => 'Selecciona la etapa del lead.',
            'estimated_value.min' => 'El valor estimado no puede ser negativo.',
            'score.min' => 'El puntaje debe ser entre 0 y 100.',
            'score.max' => 'El puntaje debe ser entre 0 y 100.',
            'owner_id.exists' => 'El usuario seleccionado no existe.',
            'notes.max' => 'Las notas no pueden superar los 2000 caracteres.',
        ];
    }
}
