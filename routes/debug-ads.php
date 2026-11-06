<?php

use App\Models\Ad;

Route::get('/debug/ads', function() {
    $ads = Ad::where('is_active', true)
        ->orderBy('display_order')
        ->get();
        
    return response()->json([
        'count' => $ads->count(),
        'ads' => $ads->map(function($ad) {
            return [
                'id' => $ad->id,
                'title' => $ad->title,
                'image_path' => $ad->image_path,
                'image_url' => $ad->image_url,
                'target_url' => $ad->target_url,
                'is_active' => $ad->is_active,
                'display_order' => $ad->display_order,
                'file_exists' => file_exists(public_path(parse_url($ad->image_url, PHP_URL_PATH)))
            ];
        })
    ]);
});
