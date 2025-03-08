<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Vanguard\Role;
use Vanguard\Support\Enum\UserStatus;
use Vanguard\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = Role::where('name', 'Admin')->first();
        $board = Role::where('name', 'Board')->first();
        $institution = Role::where('name', 'Institution')->first();

        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'eduresultchain@gmail.com',
            'username' => 'admin',
            'password' => 'admin123',
            'avatar' => null,
            'country_id' => null,
            'role_id' => $admin->id,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Board user
        User::create([
            'first_name' => 'Board',
            'last_name' => 'Member',
            'email' => 'board@eduresultchain.com',
            'username' => 'board',
            'password' => 'board123',
            'avatar' => null,
            'country_id' => null,
            'role_id' => $board->id,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Institution user
        User::create([
            'first_name' => 'Institution',
            'last_name' => 'Manager',
            'email' => 'institution@eduresultchain.com',
            'username' => 'institution',
            'password' => 'institution123',
            'avatar' => null,
            'country_id' => null,
            'role_id' => $institution->id,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
    }
}
