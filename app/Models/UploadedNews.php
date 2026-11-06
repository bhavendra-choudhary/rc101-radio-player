<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadedNews extends Model
{
    protected $fillable = [
        'title',
        'content',
        'image_path',
        'source_url',
        'source_name',
        'is_active',
        'display_order',
        'published_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('published_at', 'desc');
    }
}
