<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DefaultUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate([
            'email' => 'princenaar@gmail.com'], [
                'password' => bcrypt('Passer@789'), // Hash du mot de passe
                'name' => 'Cheikh Abdou Lahad Diagne',
            ]);

        // Vérifier que le rôle 'admin' existe et l'assigner
        $adminRole = Role::where('name', 'ADMIN')->first();
        if (! $adminRole) {
            $adminRole = Role::create(['name' => 'ADMIN']);
        }

        $admin->assignRole($adminRole);

        $chefDivision = User::firstOrCreate([
            'email' => 'dameouly@gmail.com'], [
                'password' => bcrypt('Passer@789'), // Hash du mot de passe
                'name' => 'Dame Camara',
            ]);

        $chefDivisionRole = Role::where('name', 'CHEF_DE_DIVISION')->first();
        if (! $chefDivisionRole) {
            $chefDivisionRole = Role::create(['name' => 'CHEF_DE_DIVISION']);
        }

        $chefDivision->assignRole($chefDivisionRole);

        $agent = User::firstOrCreate([
            'email' => 'diagne.cal@gmail.com'], [
                'password' => bcrypt('Passer@789'), // Hash du mot de passe
                'name' => 'Cheikh Abdou Lahad Diagne',
            ]);
        $agentRole = Role::where('name', 'ACCUEIL')->first();
        if (! $agentRole) {
            $agentRole = Role::create(['name' => 'ACCUEIL']);
        }
        $agent->assignRole($agentRole);

        $agent = User::firstOrCreate([
            'email' => 'bobbopales@gmail.com'], [
                'password' => bcrypt('Passer@789'), // Hash du mot de passe
                'name' => 'Bobbo Bassirou',
            ]);
        $agentRole = Role::where('name', 'AGENT')->first();
        if (! $agentRole) {
            $agentRole = Role::create(['name' => 'AGENT']);
        }
        $agent->assignRole($agentRole);

        $drh = User::firstOrCreate([
            'email' => 'malick.diallo@sante.gouv.sn'], [
                'password' => bcrypt('Passer@789'), // Hash du mot de passe
                'name' => 'Dr Malick Diallo',
            ]);

        // Vérifier que le rôle 'admin' existe et l'assigner
        $drhRole = Role::where('name', 'DRH')->first();
        if (! $drhRole) {
            $drhRole = Role::create(['name' => 'DRH']);
        }

        $drh->assignRole($drhRole);

    }
}
