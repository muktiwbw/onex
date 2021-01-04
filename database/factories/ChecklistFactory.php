<?php

namespace Database\Factories;

use App\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class ChecklistFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Checklist::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $answers = array_merge(range(1, 5), [null]);

        return [
            'id' => Str::uuid(),
            'body' => $this->faker->sentence(4),
            'answer' => Arr::random($answers)
        ];
    }
}
