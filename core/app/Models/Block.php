<?php
/**
 * Modified by: Talemul Islam
 * Website: https://talemul.com
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public static function factory()
    {
    }

    public function blockLines()
    {
        return $this->hasMany(BlockLine::class);
    }
}
