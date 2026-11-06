<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Api extends Model
{
    protected $fillable = [
        'name',
        'value',
        'type',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'value', // Hide API values from JSON serialization
    ];

    /**
     * Get API value by name
     */
    public static function getValue($name)
    {
        $api = self::where('name', $name)->where('is_active', true)->first();
        return $api ? $api->value : null;
    }

    /**
     * Set or update API value
     */
    public static function setValue($name, $value, $type = 'api_key', $description = null)
    {
        // Validate that value is not empty
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('API value cannot be empty');
        }

        return self::updateOrCreate(
            ['name' => $name],
            [
                'value' => trim($value),
                'type' => $type,
                'description' => $description,
                'is_active' => true
            ]
        );
    }

    /**
     * Get masked API value for display (shows only first/last few characters)
     */
    public function getMaskedValueAttribute()
    {
        if (empty($this->value)) {
            return 'Not set';
        }

        $value = $this->value;
        $length = strlen($value);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        // Show first 4 and last 4 characters
        return substr($value, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($value, -4);
    }

    /**
     * Check if API value exists and is not empty
     */
    public function hasValue()
    {
        return !empty(trim($this->value));
    }
}
