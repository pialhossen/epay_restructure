<?php
/**
 * Author: Talemul Islam
 * Website: https://talemul.com
 */
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Block;

class BlockSeeder2 extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10000; $i++) {
            $block = Block::create(['name' => 'Block ' . $i]);

            for ($j = 1; $j <= 3; $j++) {
                $block->blockLines()->create([
                    'data' => "Line {$j} for Block {$i}"
                ]);
            }
        }
    }
}
