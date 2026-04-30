<?php

namespace Database\Factories;

use App\Models\CategorieSocioprofessionnelle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategorieSocioprofessionnelle>
 */
class CategorieSocioprofessionnelleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->jobTitle(),
            'code' => fake()->optional()->bothify('CAT-###'),
            'ordre' => fake()->numberBetween(1, 99),
        ];
    }
}
