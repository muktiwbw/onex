<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class LevelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Level::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $lengths = [7, 9, 11];

        return [
            'id' => Str::uuid(),
            'tujuan' => $this->faker->sentence(Arr::random($lengths)),
            'uraian' => $this->faker->paragraph(Arr::random($lengths)),
            'examThreshold' => 80,
            'evaluationThreshold' => 80
        ];
    }
}
