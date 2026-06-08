<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folio' => 'POL-'.fake()->unique()->numberBetween(1, 999999),
            'entry_date' => fake()->dateTimeBetween('-90 days', 'now'),
            'concept' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'status' => 'posted',
            'posted_by' => User::factory(),
            'posted_at' => now(),
            'total_debit' => 0,
            'total_credit' => 0,
        ];
    }
}
