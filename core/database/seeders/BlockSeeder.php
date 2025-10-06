<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Block;
use App\Models\BlockLine;

class BlockSeeder extends Seeder
{
    public function run(): void
    {
        Block::factory()
            ->count(50)
            ->hasBlockLines(1)
            ->create();
    }
}
