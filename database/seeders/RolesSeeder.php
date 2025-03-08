<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Vanguard\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'name' => 'Admin',
            'display_name' => 'Admin',
            'description' => 'System administrator.',
            'removable' => false,
        ]);

        Role::create([
            'name' => 'Board',
            'display_name' => 'Board',
            'description' => 'Board member.',
            'removable' => false,
        ]);
        
        Role::create([
            'name' => 'Institution',
            'display_name' => 'Institution',
            'description' => 'Institution member.',
            'removable' => false,
        ]);

        Role::create([
            'name' => 'User',
            'display_name' => 'User',
            'description' => 'Default system user.',
            'removable' => false,
        ]);
    }
}
