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

        User::create([
            'first_name' => 'EduResultChain',
            'email' => 'eduresultchain@gmail.com',
            'username' => 'admin',
            'password' => 'admin123',
            'avatar' => 'tIIuIFMGtxQaTl3O2OdvxjG3cjkWdFLLchv7Muur.png',
            'country_id' => null,
            'role_id' => $admin->id,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
    }
}
