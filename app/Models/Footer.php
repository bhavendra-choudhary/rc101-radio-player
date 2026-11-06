<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Footer extends Model
{
    protected $fillable = [
        // Company Info Section
        'brand_name',
        'description',

        // Quick Links Section
        'home_link_text',
        'home_link_url',
        'news_link_text',
        'news_link_url',
        'concerts_link_text',
        'concerts_link_url',
        'events_link_text',
        'events_link_url',
        'contact_link_text',
        'contact_link_url',

        // Contact Info Section
        'address',
        'phone',
        'email',
        'frequency',

        // Social Media Links
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'youtube_url',

        // Copyright
        'copyright_text',

        // Theme Settings
        'theme_settings',
        'seo_settings',
    ];

    protected $casts = [
        'theme_settings' => 'array',
        'seo_settings' => 'array',
    ];

    /**
     * Get the first footer record or create a default one
     */
    public static function getFooter()
    {
        try {
            $footer = self::first();
            if (!$footer) {
                $footer = new self();
                $footer->brand_name = 'Radio Station';
                $footer->description = 'Your source for rockin country music, news, and entertainment. Tune in 24/7.';
                $footer->home_link_text = 'Home';
                $footer->home_link_url = '#';
                $footer->news_link_text = 'News';
                $footer->news_link_url = '#news';
                $footer->concerts_link_text = 'Concerts';
                $footer->concerts_link_url = '#concerts';
                $footer->events_link_text = 'Events';
                $footer->events_link_url = '#events';
                $footer->contact_link_text = 'Contact';
                $footer->contact_link_url = '#contact';
                $footer->address = '123 Radio Street, City, Country';
                $footer->phone = '+1 (123) 456-7890';
                $footer->email = 'info@radiostation.com';
                $footer->frequency = '98.5 FM';
                $footer->facebook_url = '#';
                $footer->instagram_url = '#';
                $footer->twitter_url = '#';
                $footer->youtube_url = '#';
                $footer->copyright_text = '2023 Radio Station. All Rights Reserved.';
                $footer->seo_settings = [
                    'title'       => config('app.name') . ' — #1 Hit Music Radio, Stream Live 24/7 Free',
                    'description' => 'Listen to ' . config('app.name') . ' live online — your #1 hit music radio station. Stream the hottest songs, latest pop hits, and non-stop music 24 hours a day, 7 days a week. Totally free.',
                    'image'       => 'images/social-banner.png',
                ];

                // Default theme settings
                $footer->theme_settings = [
                    'primary_blue' => '#2c3e50',
                    'secondary_blue' => '#34495e',
                    'accent_blue' => '#3498db',
                    'accent_orange' => '#e67e22',
                    'accent_teal' => '#16a085',
                    'warm_white' => '#fdfdfd',
                    'light_gray' => '#f8f9fa',
                    'soft_yellow' => '#fffef7',
                    'light_yellow' => '#fefcf0',
                    'medium_gray' => '#6c757d',
                    'dark_gray' => '#2c3e50',
                    'text_color' => '#495057',
                    'border_color' => '#e9ecef',
                    'success_green' => '#27ae60',
                    'player_height' => '80px'
                ];

                $footer->save();
            }
            return $footer;
        } catch (\Exception $e) {
            // Return a default footer object if database fails
            $footer = new self();
            $footer->brand_name = 'Radio Station';
            $footer->description = 'Your favorite source for music, news, and entertainment. Tune in 24/7 for the best experience.';
            $footer->home_link_text = 'Home';
            $footer->home_link_url = '#';
            $footer->news_link_text = 'News';
            $footer->news_link_url = '#news';
            $footer->concerts_link_text = 'Concerts';
            $footer->concerts_link_url = '#concerts';
            $footer->events_link_text = 'Events';
            $footer->events_link_url = '#events';
            $footer->contact_link_text = 'Contact';
            $footer->contact_link_url = '#contact';
            $footer->address = '123 Radio Street, City, Country';
            $footer->phone = '+1 (123) 456-7890';
            $footer->email = 'info@radiostation.com';
            $footer->frequency = '98.5 FM';
            $footer->facebook_url = '#';
            $footer->instagram_url = '#';
            $footer->twitter_url = '#';
            $footer->youtube_url = '#';
            $footer->copyright_text = '2023 Radio Station. All Rights Reserved.';

            // Default theme settings
            $footer->theme_settings = [
                'primary_blue' => '#2c3e50',
                'secondary_blue' => '#34495e',
                'accent_blue' => '#3498db',
                'accent_orange' => '#e67e22',
                'accent_teal' => '#16a085',
                'warm_white' => '#fdfdfd',
                'light_gray' => '#f8f9fa',
                'soft_yellow' => '#fffef7',
                'light_yellow' => '#fefcf0',
                'medium_gray' => '#6c757d',
                'dark_gray' => '#2c3e50',
                'text_color' => '#495057',
                'border_color' => '#e9ecef',
                'success_green' => '#27ae60',
                'player_height' => '80px'
            ];

            return $footer;
        }
    }
}