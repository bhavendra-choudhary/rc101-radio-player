<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestImage extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'contest_id',
        'image_path',
        'title',
        'description',
        'display_order'
    ];
    
    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }
}
