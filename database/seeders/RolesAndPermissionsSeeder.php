<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $chefDivisionRole = Role::firstOrCreate(['name' => 'CHEF_DE_DIVISION']);
        $agentRole = Role::firstOrCreate(['name' => 'AGENT']);
        $drhRole = Role::firstOrCreate(['name' => 'DRH']);
    }
}
