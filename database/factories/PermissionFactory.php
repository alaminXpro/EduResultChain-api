<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Vanguard\Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::random(5),
            'display_name' => implode(' ', $this->faker->words(2)),
            'description' => substr($this->faker->paragraph, 0, 191),
            'removable' => true,
        ];
    }
}
