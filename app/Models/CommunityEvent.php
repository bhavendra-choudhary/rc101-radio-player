<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityEvent extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'event_date',
        'location',
        'source_url',
        'status',
        'display_order'
    ];

    protected $casts = [
        'event_date' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('event_date', 'asc');
    }
}
