<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Vanguard\Role;
use Vanguard\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email' => $this->faker->email,
            'password' => '$2y$10$A2A/2IIP.jsLzIiAPr.enuzxzRWzIzLWifqNU33PWPBGx6mkJFz72', // 123123123
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'phone' => $this->faker->phoneNumber,
            'avatar' => null,
            'address' => $this->faker->address,
            'country_id' => function () {
                return $this->faker->randomElement(\Vanguard\Country::pluck('id')->toArray());
            },
            'role_id' => function () {
                return Role::factory()->create()->id;
            },
            'status' => \Vanguard\Support\Enum\UserStatus::ACTIVE,
            'birthday' => $this->faker->date(),
            'email_verified_at' => (string) now(),
        ];
    }
}
