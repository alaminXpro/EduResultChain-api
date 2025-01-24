<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(\Database\Seeders\CountriesSeeder::class);
        $this->call(\Database\Seeders\RolesSeeder::class);
        $this->call(\Database\Seeders\PermissionsSeeder::class);
        $this->call(\Database\Seeders\UserSeeder::class);
        $this->call(\Vanguard\Announcements\Database\Seeders\AnnouncementsDatabaseSeeder::class);

        Model::reguard();
    }
}
