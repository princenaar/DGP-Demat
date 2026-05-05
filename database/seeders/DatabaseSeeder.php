<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DefaultUsersSeeder::class,
            StructureSeeder::class,
            TypeDocumentSeeder::class,
            EtatDemandeSeeder::class,
            CategorieSocioprofessionnelleSeeder::class,
            PieceRequiseSeeder::class,
            WorkflowTransitionSeeder::class,
        ]);
    }
}
