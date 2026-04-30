<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(),
            'plan_id' => fake()->unique()->numberBetween(1, 50),
        ];
    }
}
