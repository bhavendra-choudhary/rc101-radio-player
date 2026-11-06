<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key with cache and fallback.
     */
    public static function get(string $key, $default = null)
    {
        try {
            return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
                $setting = static::where('key', $key)->first();
                if ($setting) {
                    return $setting->value ?? '';
                }
                return $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set/update a setting value by key and clear cache.
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => ($value === null) ? '' : (string) $value]
        );
        Cache::forget("site_setting_{$key}");
    }

    /**
     * Get the active format configuration based on the current station format.
     */
    public static function getFormatConfig(?string $format = null): array
    {
        $formatKey = strtolower(trim($format ?? (string) static::get('station_format', env('STATION_FORMAT', 'country'))));
        if (empty($formatKey)) {
            $formatKey = 'country';
        }
        $configs = config('app.format_config', []);

        if (isset($configs[$formatKey])) {
            $config = $configs[$formatKey];
            $config['key'] = $formatKey;
            return $config;
        }

        // Fallback synthesis for any custom genre entered
        $capitalized = ucwords(str_replace(['-', '_'], ' ', $formatKey));
        return [
            'key'                => $formatKey,
            'label'              => $capitalized,
            'label_plural'       => $capitalized,
            'concert_genre'      => $formatKey,
            'ticketmaster_genre' => $capitalized,
            'news_keywords'      => "{$formatKey} music news",
            'concert_heading'    => "Discover top {$capitalized} music concerts happening",
            'news_section_title' => "{$capitalized} Music News",
            'concert_icon'       => 'fas fa-music',
        ];
    }
}

