<?php
/**
 * Modified by: Talemul Islam
 * Website: https://talemul.com
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockLine extends Model
{
    use HasFactory;
    protected $fillable = ['block_id', 'data'];

    public function block()
    {
        return $this->belongsTo(Block::class);
    }
}
