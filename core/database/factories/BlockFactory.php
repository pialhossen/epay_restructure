<?php
/**
 * Author: Talemul Islam
 * Website: https://talemul.com
 */
use App\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockFactory extends Factory
{
    protected $model = Block::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(2),
        ];
    }
}
