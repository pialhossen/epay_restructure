<?php
/**
 * Author: Talemul Islam
 * Website: https://talemul.com
 */
use App\Models\BlockLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockLineFactory extends Factory
{
    protected $model = BlockLine::class;

    public function definition(): array
    {
        return [
            'data' => $this->faker->sentence(),
        ];
    }
}
