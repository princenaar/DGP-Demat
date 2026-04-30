<?php

namespace Database\Factories;

use App\Models\PieceRequise;
use App\Models\TypeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PieceRequise>
 */
class PieceRequiseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type_document_id' => TypeDocument::create([
                'nom' => 'Document de test',
                'code' => fake()->unique()->bothify('TST##'),
            ])->id,
            'libelle' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'obligatoire' => fake()->boolean(80),
            'ordre' => fake()->numberBetween(1, 10),
        ];
    }
}
