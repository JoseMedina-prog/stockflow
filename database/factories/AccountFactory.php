<?php

namespace Database\Factories;

use App\Enums\AccountNormalBalance;
use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => (string) fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->words(3, true),
            'type' => AccountType::Asset->value,
            'normal_balance' => AccountNormalBalance::Debit->value,
            'is_active' => true,
            'is_system' => false,
            'sort' => 0,
        ];
    }
}
