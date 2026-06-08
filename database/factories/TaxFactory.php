<?php

namespace Database\Factories;

use App\Enums\TaxType;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    protected $model = Tax::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'IVA-'.fake()->unique()->numberBetween(1, 999),
            'name' => 'IVA 16%',
            'type' => TaxType::IvaTrasladado->value,
            'rate' => 0.16,
            'is_active' => true,
            'is_inclusive' => false,
        ];
    }

    public function ivaTrasladado(): static
    {
        return $this->state([
            'code' => 'IVAT-16',
            'name' => 'IVA 16% (Trasladado)',
            'type' => TaxType::IvaTrasladado->value,
            'rate' => 0.16,
        ]);
    }

    public function ivaAcreditable(): static
    {
        return $this->state([
            'code' => 'IVAA-16',
            'name' => 'IVA 16% (Acreditable)',
            'type' => TaxType::IvaAcreditable->value,
            'rate' => 0.16,
        ]);
    }

    public function exento(): static
    {
        return $this->state([
            'code' => 'EXENTO',
            'name' => 'Exento de IVA',
            'type' => TaxType::Otro->value,
            'rate' => 0,
        ]);
    }
}
