<?php
/**
 * Author: Talemul Islam
 * Website: https://talemul.com
 */
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BlockSeeder2::class,
        ]);
    }
}
