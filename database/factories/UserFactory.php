<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => 'password',
            'role' => Role::Keuangan,
            'aktif' => true,
            'harus_ganti_password' => false,
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(['role' => $role]);
    }
}
