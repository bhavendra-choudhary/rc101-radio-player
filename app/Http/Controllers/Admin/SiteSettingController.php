<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Footer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SiteSettingController extends Controller
{
    /**
     * Get array of all setting keys with defaults.
     */
    public static function getAllSettings(): array
    {
        $footer = Footer::getFooter();

        return [
            // 1. Station
            'app_name' => SiteSetting::get('app_name', $footer->brand_name ?? config('app.name')),
            'station_format' => SiteSetting::get('station_format', env('STATION_FORMAT', 'country')),
            'station_slogan' => SiteSetting::get('station_slogan', env('STATION_SLOGAN', 'YOUR FAVORITE LIVE RADIO STATION')),
            'station_frequency' => SiteSetting::get('station_frequency', $footer->frequency ?? ''),
            'station_phone' => SiteSetting::get('station_phone', $footer->phone ?? ''),
            'station_email' => SiteSetting::get('station_email', $footer->email ?? ''),
            'station_address' => SiteSetting::get('station_address', $footer->address ?? ''),
            'website_url' => SiteSetting::get('website_url', url('/')),
            'external_urls' => SiteSetting::get('external_urls', ''),

            // 2. Branding
            'logo_path' => SiteSetting::get('logo_path', 'Landscape_Logo.png'),
            'favicon_path' => SiteSetting::get('favicon_path', 'favicon.ico'),

            // 3. Stream Player
            'stream_url' => SiteSetting::get('stream_url', env('STREAM_URL', 'https://de1.api.radio-browser.info/json/stations/topvote/100')),
            'backup_stream_url' => SiteSetting::get('backup_stream_url', ''),
            'stream_mount_point' => SiteSetting::get('stream_mount_point', '/stream'),
            'autoplay_enabled' => SiteSetting::get('autoplay_enabled', '0'),

            // 4. Artwork
            'default_artwork_path' => SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')),
            'fallback_artwork_url' => SiteSetting::get('fallback_artwork_url', ''),

            // 5. Banners
            'banner_image' => SiteSetting::get('banner_image', ''),
            'banner_destination_url' => self::sanitizeUrl(SiteSetting::get('banner_destination_url', '')),
            'banner_title' => SiteSetting::get('banner_title', 'Listen Live On Any Device'),
            'banner_subtitle' => SiteSetting::get('banner_subtitle', 'Download our app or stream online 24/7'),
            'app_store_url' => self::sanitizeUrl(SiteSetting::get('app_store_url', '')),
            'play_store_url' => self::sanitizeUrl(SiteSetting::get('play_store_url', '')),

            // 6. News
            'news_api_key' => SiteSetting::get('news_api_key', ''),
            'news_api_endpoint' => SiteSetting::get('news_api_endpoint', 'https://newsapi.org/v2/'),
            'news_genre' => SiteSetting::get('news_genre', 'country'),
            'news_search_query' => SiteSetting::get('news_search_query', ''),
            'news_country' => SiteSetting::get('news_country', 'us'),
            'news_items_limit' => SiteSetting::get('news_items_limit', '12'),
            'default_news_source' => SiteSetting::get('default_news_source', $footer->brand_name ?? 'Radio News'),

            // 7. Concerts
            'concerts_api_key' => SiteSetting::get('concerts_api_key', ''),
            'concerts_default_location' => SiteSetting::get('concerts_default_location', ''),
            'concerts_radius' => SiteSetting::get('concerts_radius', '50'),

            // 8. Website
            'website_title' => SiteSetting::get('website_title', ($footer->brand_name ?? SiteSetting::get('app_name', config('app.name'))) . ' - Live Radio & Music'),
            'website_subtitle' => SiteSetting::get('website_subtitle', 'Streaming Non-Stop Hits 24/7'),
            'copyright_text' => SiteSetting::get('copyright_text', $footer->copyright_text ?? ('© ' . date('Y') . ' ' . SiteSetting::get('app_name', config('app.name')) . '. All Rights Reserved.')),
            'maintenance_notice' => SiteSetting::get('maintenance_notice', ''),

            // 9. Colors (Descriptive Labels Required)
            'player_song_title_color' => SiteSetting::get('player_song_title_color', '#ffffff'),
            'player_artist_name_color' => SiteSetting::get('player_artist_name_color', '#3498db'),
            'player_background_color' => SiteSetting::get('player_background_color', '#2c3e50'),
            'main_text_color' => SiteSetting::get('main_text_color', '#495057'),
            'heading_color' => SiteSetting::get('heading_color', '#2c3e50'),
            'link_color' => SiteSetting::get('link_color', '#3498db'),
            'button_bg_color' => SiteSetting::get('button_bg_color', '#2c3e50'),
            'button_text_color' => SiteSetting::get('button_text_color', '#ffffff'),
            'header_bg_color' => SiteSetting::get('header_bg_color', '#ffffff'),
            'footer_bg_color' => SiteSetting::get('footer_bg_color', '#2c3e50'),
            'footer_text_color' => SiteSetting::get('footer_text_color', '#f8f9fa'),
            'nav_bg_color' => SiteSetting::get('nav_bg_color', '#ffffff'),
            'nav_text_color' => SiteSetting::get('nav_text_color', '#2c3e50'),

            // 10. SEO & Indexing
            'seo_title' => SiteSetting::get('seo_title', $footer->seo_settings['title'] ?? 'Live Stream Radio Station'),
            'seo_description' => SiteSetting::get('seo_description', $footer->seo_settings['description'] ?? 'Stream live radio, music, news and concerts.'),
            'seo_keywords' => SiteSetting::get('seo_keywords', 'radio, live stream, music, hits'),
            'seo_image' => SiteSetting::get('seo_image', $footer->seo_settings['image'] ?? 'images/social-banner.png'),
            'canonical_url' => SiteSetting::get('canonical_url', url('/')),
            'robots_indexing' => SiteSetting::get('robots_indexing', 'index, follow'),

            // 11. Social Sharing & Open Graph
            'og_title' => SiteSetting::get('og_title', ''),
            'og_description' => SiteSetting::get('og_description', ''),
            'og_image' => SiteSetting::get('og_image', ''),
            'facebook_share_image' => SiteSetting::get('facebook_share_image', ''),
            'text_share_image' => SiteSetting::get('text_share_image', ''),
            'twitter_card' => SiteSetting::get('twitter_card', 'summary_large_image'),
            'twitter_site' => SiteSetting::get('twitter_site', ''),
            'twitter_title' => SiteSetting::get('twitter_title', ''),
            'twitter_description' => SiteSetting::get('twitter_description', ''),
            'twitter_image' => SiteSetting::get('twitter_image', ''),
            'facebook_url' => self::sanitizeUrl(SiteSetting::get('facebook_url', $footer->facebook_url ?? '')),
            'instagram_url' => self::sanitizeUrl(SiteSetting::get('instagram_url', $footer->instagram_url ?? '')),
            'twitter_url' => self::sanitizeUrl(SiteSetting::get('twitter_url', $footer->twitter_url ?? '')),
            'youtube_url' => self::sanitizeUrl(SiteSetting::get('youtube_url', $footer->youtube_url ?? '')),
            'social_share_image' => SiteSetting::get('social_share_image', ''),

            // 12. Affiliate Links
            'affiliate_link_1_label' => SiteSetting::get('affiliate_link_1_label', ''),
            'affiliate_link_1_url' => self::sanitizeUrl(SiteSetting::get('affiliate_link_1_url', '')),
            'affiliate_link_2_label' => SiteSetting::get('affiliate_link_2_label', ''),
            'affiliate_link_2_url' => self::sanitizeUrl(SiteSetting::get('affiliate_link_2_url', '')),
            'affiliate_link_3_label' => SiteSetting::get('affiliate_link_3_label', ''),
            'affiliate_link_3_url' => self::sanitizeUrl(SiteSetting::get('affiliate_link_3_url', '')),

            // 13. Front Page
            'front_hero_heading' => SiteSetting::get('front_hero_heading', 'Welcome to Live Station'),
            'front_hero_subheading' => SiteSetting::get('front_hero_subheading', 'Listen live 24/7 on any device'),
            'front_featured_title' => SiteSetting::get('front_featured_title', 'Latest News & Events'),
            'front_request_button_text' => SiteSetting::get('front_request_button_text', 'Request a Song'),
            'front_about_text' => SiteSetting::get('front_about_text', $footer->description ?? 'Your favorite source for music, news, and entertainment.'),
        ];
    }

    /**
     * Sanitize and format external URL.
     * Returns empty string if null, empty, #, or invalid.
     */
    public static function sanitizeUrl(?string $url): string
    {
        if ($url === null) {
            return '';
        }
        $url = trim($url);
        if ($url === '' || in_array(strtolower($url), ['#', 'javascript:void(0)', 'javascript:void(0);', '/#', 'null', 'undefined'])) {
            return '';
        }
        return $url;
    }

    /**
     * Display settings page.
     */
    public function index()
    {
        $settings = self::getAllSettings();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Store/Update settings.
     */
    public function update(Request $request)
    {
        $rules = [
            'app_name' => 'required|string|max:255',
            'station_format' => 'nullable|string|max:50',
            'station_slogan' => 'nullable|string|max:255',
            'station_frequency' => 'nullable|string|max:255',
            'station_phone' => 'nullable|string|max:255',
            'station_email' => 'nullable|email|max:255',
            'station_address' => 'nullable|string|max:255',
            'website_url' => 'nullable|string|max:255',
            'external_urls' => 'nullable|string',

            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'favicon' => 'nullable|file|mimes:png,ico,jpeg,jpg,svg|max:1024',

            'stream_url' => 'required|string|max:500',
            'backup_stream_url' => 'nullable|string|max:500',
            'stream_mount_point' => 'nullable|string|max:255',
            'autoplay_enabled' => 'nullable|boolean',

            'default_artwork' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'fallback_artwork_url' => 'nullable|string|max:500',

            'banner_image_file' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'banner_destination_url' => 'nullable|string|max:500',
            'banner_title' => 'nullable|string|max:255',
            'banner_subtitle' => 'nullable|string|max:255',
            'app_store_url' => 'nullable|string|max:500',
            'play_store_url' => 'nullable|string|max:500',

            'news_api_key' => 'nullable|string|max:255',
            'news_api_endpoint' => 'nullable|string|max:500',
            'news_genre' => 'nullable|string|max:100',
            'news_search_query' => 'nullable|string|max:500',
            'news_country' => 'nullable|string|max:10',
            'news_items_limit' => 'nullable|integer|min:1|max:50',
            'default_news_source' => 'nullable|string|max:255',

            'concerts_api_key' => 'nullable|string|max:255',
            'concerts_default_location' => 'nullable|string|max:255',
            'concerts_radius' => 'nullable|integer|min:1|max:500',

            'website_title' => 'nullable|string|max:255',
            'website_subtitle' => 'nullable|string|max:255',
            'copyright_text' => 'nullable|string|max:255',
            'maintenance_notice' => 'nullable|string|max:500',

            'player_song_title_color' => 'required|string|max:7',
            'player_artist_name_color' => 'required|string|max:7',
            'player_background_color' => 'required|string|max:7',
            'main_text_color' => 'required|string|max:7',
            'heading_color' => 'required|string|max:7',
            'link_color' => 'required|string|max:7',
            'button_bg_color' => 'required|string|max:7',
            'button_text_color' => 'required|string|max:7',
            'header_bg_color' => 'required|string|max:7',
            'footer_bg_color' => 'required|string|max:7',
            'footer_text_color' => 'required|string|max:7',
            'nav_bg_color' => 'required|string|max:7',
            'nav_text_color' => 'required|string|max:7',

            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:500',
            'seo_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'canonical_url' => 'nullable|string|max:500',
            'robots_indexing' => 'nullable|string|max:100',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'facebook_share_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'text_share_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'twitter_card' => 'nullable|string|max:50',
            'twitter_site' => 'nullable|string|max:100',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'facebook_url' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:255',
            'twitter_url' => 'nullable|string|max:255',
            'youtube_url' => 'nullable|string|max:255',

            'affiliate_link_1_label' => 'nullable|string|max:255',
            'affiliate_link_1_url' => 'nullable|string|max:500',
            'affiliate_link_2_label' => 'nullable|string|max:255',
            'affiliate_link_2_url' => 'nullable|string|max:500',
            'affiliate_link_3_label' => 'nullable|string|max:255',
            'affiliate_link_3_url' => 'nullable|string|max:500',

            'front_hero_heading' => 'nullable|string|max:255',
            'front_hero_subheading' => 'nullable|string|max:500',
            'front_featured_title' => 'nullable|string|max:255',
            'front_request_button_text' => 'nullable|string|max:255',
            'front_about_text' => 'nullable|string|max:1000',
        ];

        $request->validate($rules);

        // Process File Uploads
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoName = 'Landscape_Logo.' . $logo->getClientOriginalExtension();
            $logo->move(public_path(), $logoName);
            SiteSetting::set('logo_path', $logoName);
        }

        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon');
            $ext = $favicon->getClientOriginalExtension();
            $favName = 'favicon.' . $ext;
            $favicon->move(public_path(), $favName);
            SiteSetting::set('favicon_path', $favName);
        }

        if ($request->hasFile('default_artwork')) {
            $artwork = $request->file('default_artwork');
            $artName = 'images/default_artwork_' . time() . '.' . $artwork->getClientOriginalExtension();
            if (!is_dir(public_path('images'))) {
                mkdir(public_path('images'), 0755, true);
            }
            $artwork->move(public_path('images'), basename($artName));
            SiteSetting::set('default_artwork_path', $artName);
        }

        if ($request->hasFile('banner_image_file')) {
            $bannerFile = $request->file('banner_image_file');
            $bName = 'images/banner_' . time() . '.' . $bannerFile->getClientOriginalExtension();
            if (!is_dir(public_path('images'))) {
                mkdir(public_path('images'), 0755, true);
            }
            $bannerFile->move(public_path('images'), basename($bName));
            SiteSetting::set('banner_image', $bName);
        }

        if ($request->hasFile('seo_image_file')) {
            $seoImg = $request->file('seo_image_file');
            $sName = 'seo/seo_share_' . time() . '.' . $seoImg->getClientOriginalExtension();
            if (!is_dir(public_path('seo'))) {
                mkdir(public_path('seo'), 0755, true);
            }
            $seoImg->move(public_path('seo'), basename($sName));
            SiteSetting::set('seo_image', $sName);
        }

        if ($request->hasFile('og_image_file')) {
            $ogImg = $request->file('og_image_file');
            $sName = 'seo/og_share_' . time() . '.' . $ogImg->getClientOriginalExtension();
            if (!is_dir(public_path('seo'))) {
                mkdir(public_path('seo'), 0755, true);
            }
            $ogImg->move(public_path('seo'), basename($sName));
            SiteSetting::set('og_image', $sName);
        }

        if ($request->hasFile('facebook_share_image_file')) {
            $fbImg = $request->file('facebook_share_image_file');
            $sName = 'seo/fb_share_' . time() . '.' . $fbImg->getClientOriginalExtension();
            if (!is_dir(public_path('seo'))) {
                mkdir(public_path('seo'), 0755, true);
            }
            $fbImg->move(public_path('seo'), basename($sName));
            SiteSetting::set('facebook_share_image', $sName);
        }

        if ($request->hasFile('text_share_image_file')) {
            $txtImg = $request->file('text_share_image_file');
            $sName = 'seo/text_share_' . time() . '.' . $txtImg->getClientOriginalExtension();
            if (!is_dir(public_path('seo'))) {
                mkdir(public_path('seo'), 0755, true);
            }
            $txtImg->move(public_path('seo'), basename($sName));
            SiteSetting::set('text_share_image', $sName);
        }

        if ($request->hasFile('twitter_image_file')) {
            $twImg = $request->file('twitter_image_file');
            $sName = 'seo/twitter_share_' . time() . '.' . $twImg->getClientOriginalExtension();
            if (!is_dir(public_path('seo'))) {
                mkdir(public_path('seo'), 0755, true);
            }
            $twImg->move(public_path('seo'), basename($sName));
            SiteSetting::set('twitter_image', $sName);
        }

        // Process Text/Form fields
        $fieldsToSave = [
            'app_name', 'station_format', 'station_slogan', 'station_frequency', 'station_phone', 'station_email', 'station_address', 'website_url', 'external_urls',
            'stream_url', 'backup_stream_url', 'stream_mount_point', 'autoplay_enabled',
            'fallback_artwork_url', 'banner_destination_url', 'banner_title', 'banner_subtitle', 'app_store_url', 'play_store_url',
            'news_api_key', 'news_api_endpoint', 'news_genre', 'news_search_query', 'news_country', 'news_items_limit', 'default_news_source',
            'concerts_api_key', 'concerts_default_location', 'concerts_radius',
            'website_title', 'website_subtitle', 'copyright_text', 'maintenance_notice',
            'player_song_title_color', 'player_artist_name_color', 'player_background_color',
            'main_text_color', 'heading_color', 'link_color', 'button_bg_color', 'button_text_color',
            'header_bg_color', 'footer_bg_color', 'footer_text_color', 'nav_bg_color', 'nav_text_color',
            'seo_title', 'seo_description', 'seo_keywords', 'canonical_url', 'robots_indexing',
            'og_title', 'og_description', 'twitter_card', 'twitter_site', 'twitter_title', 'twitter_description',
            'facebook_url', 'instagram_url', 'twitter_url', 'youtube_url',
            'affiliate_link_1_label', 'affiliate_link_1_url',
            'affiliate_link_2_label', 'affiliate_link_2_url',
            'affiliate_link_3_label', 'affiliate_link_3_url',
            'front_hero_heading', 'front_hero_subheading', 'front_featured_title', 'front_request_button_text', 'front_about_text',
        ];

        $urlFields = [
            'website_url',
            'stream_url',
            'backup_stream_url',
            'fallback_artwork_url',
            'banner_destination_url',
            'app_store_url',
            'play_store_url',
            'news_api_endpoint',
            'canonical_url',
            'facebook_url',
            'instagram_url',
            'twitter_url',
            'youtube_url',
            'affiliate_link_1_url',
            'affiliate_link_2_url',
            'affiliate_link_3_url',
        ];

        foreach ($fieldsToSave as $field) {
            $val = $request->input($field);
            $val = ($val === null) ? '' : trim((string) $val);
            if (in_array($field, $urlFields)) {
                $val = self::sanitizeUrl($val);
                // Prepend https:// if user provided a plain domain (e.g. instagram.com/myradio)
                if ($val !== '' && !preg_match('#^https?://#i', $val) && !str_starts_with($val, '/') && !str_starts_with($val, 'mailto:')) {
                    $val = 'https://' . ltrim($val, '/');
                }
            }
            SiteSetting::set($field, $val);
        }

        // Synchronize with Footer Model
        try {
            $footer = Footer::getFooter();
            $footer->brand_name = $request->input('app_name') !== null ? trim((string)$request->input('app_name')) : ($footer->brand_name ?? '');
            $footer->description = $request->input('front_about_text') !== null ? trim((string)$request->input('front_about_text')) : ($footer->description ?? '');
            $footer->phone = $request->input('station_phone') !== null ? trim((string)$request->input('station_phone')) : '';
            $footer->email = $request->input('station_email') !== null ? trim((string)$request->input('station_email')) : '';
            $footer->address = $request->input('station_address') !== null ? trim((string)$request->input('station_address')) : '';
            $footer->frequency = $request->input('station_frequency') !== null ? trim((string)$request->input('station_frequency')) : '';
            $footer->copyright_text = $request->input('copyright_text') !== null ? trim((string)$request->input('copyright_text')) : '';
            $footer->facebook_url = self::sanitizeUrl($request->input('facebook_url'));
            $footer->instagram_url = self::sanitizeUrl($request->input('instagram_url'));
            $footer->twitter_url = self::sanitizeUrl($request->input('twitter_url'));
            $footer->youtube_url = self::sanitizeUrl($request->input('youtube_url'));

            $footer->seo_settings = [
                'title' => $request->input('seo_title', $footer->seo_settings['title'] ?? ''),
                'description' => $request->input('seo_description', $footer->seo_settings['description'] ?? ''),
                'image' => SiteSetting::get('seo_image', $footer->seo_settings['image'] ?? ''),
            ];

            $footer->save();
        } catch (\Exception $e) {
            Log::error('Error syncing Footer model: ' . $e->getMessage());
        }

        // Update public files (slogan.txt, streamUrl.txt)
        if ($request->input('station_slogan')) {
            @file_put_contents(public_path('config/slogan.txt'), trim($request->input('station_slogan')));
        }
        if ($request->input('stream_url')) {
            @file_put_contents(public_path('config/streamUrl.txt'), trim($request->input('stream_url')));
        }

        // Regenerate theme.css with descriptive variables
        $this->generateDescriptiveThemeCss();

        // Clear all caches so settings and footer updates take effect immediately
        Cache::flush();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Station configuration updated successfully across all 13 modules!');
    }

    /**
     * Generate theme.css with descriptive CSS variables
     */
    private function generateDescriptiveThemeCss(): void
    {
        try {
            $colors = [
                'player-song-title-color' => SiteSetting::get('player_song_title_color', '#ffffff'),
                'player-artist-name-color' => SiteSetting::get('player_artist_name_color', '#3498db'),
                'player-background-color' => SiteSetting::get('player_background_color', '#2c3e50'),
                'main-text-color' => SiteSetting::get('main_text_color', '#495057'),
                'heading-color' => SiteSetting::get('heading_color', '#2c3e50'),
                'link-color' => SiteSetting::get('link_color', '#3498db'),
                'button-bg-color' => SiteSetting::get('button_bg_color', '#2c3e50'),
                'button-text-color' => SiteSetting::get('button_text_color', '#ffffff'),
                'header-bg-color' => SiteSetting::get('header_bg_color', '#ffffff'),
                'footer-bg-color' => SiteSetting::get('footer_bg_color', '#2c3e50'),
                'footer-text-color' => SiteSetting::get('footer_text_color', '#f8f9fa'),
                'nav-bg-color' => SiteSetting::get('nav_bg_color', '#ffffff'),
                'nav-text-color' => SiteSetting::get('nav_text_color', '#2c3e50'),
            ];

            $cssContent = ":root {\n";
            foreach ($colors as $varName => $val) {
                $cssContent .= "    --{$varName}: {$val};\n";
            }
            $cssContent .= "}\n\n";

            // Comprehensive CSS selectors mapped to each descriptive color token
            $cssContent .= <<<CSS
/* 1. Media Player Song Title Color */
#current-title, #current-title *, .player-song, #mini-song, .player-song-title, .track-title, .live-song-title, #song-title, .song-title, 
.current-song-title, .now-playing-title, .hero-player-title, #playerSongTitle {
    color: var(--player-song-title-color) !important;
}

/* 2. Media Player Artist Name Color */
#current-artist, #current-artist *, .player-artist-name, .track-artist, .live-artist-name, #song-artist, .artist-name, 
.current-artist-name, .now-playing-artist, .hero-player-artist, #playerArtistName {
    color: var(--player-artist-name-color) !important;
}

/* 3. Media Player Background Color */
#sticky-player, .sticky-player, .bottom-player, .player-bar, #bottomPlayer, .audio-player-container, 
.player-wrapper, .compact-player, .car-player-container {
    background-color: var(--player-background-color) !important;
    background: var(--player-background-color) !important;
}

/* 4. Main Website Text Color */
body, p, .text-content, .lead, .news-card-description, .concert-meta-info, 
.contest-description, .contact-info-text {
    color: var(--main-text-color) !important;
}

/* 5. Heading Color */
h1, h2, h3, h4, h5, h6, .display-1, .display-2, .display-3, .display-4, .display-5, 
.section-header h3, .news-card-title, .concert-card .card-title, .modal-title, .concerts-nearby-title {
    color: var(--heading-color) !important;
}

/* 6. Link Color */
a:not(.btn):not(.nav-link), .news-card-link, .read-more-link, .ticket-link, .footer-links a {
    color: var(--link-color) !important;
}

/* 7. Button Background Color */
.btn-primary, .play-btn, .btn-action, .btn-submit, .btn-request, .load-more-btn {
    background-color: var(--button-bg-color) !important;
    border-color: var(--button-bg-color) !important;
}

/* 8. Button Text Color */
.btn-primary, .btn-primary *, .play-btn, .btn-action, .btn-submit, .btn-request, .load-more-btn {
    color: var(--button-text-color) !important;
}

/* 9. Header Background Color */
.site-header, .top-header, .header-promo-strip, #topHeader, .announcement-banner {
    background-color: var(--header-bg-color) !important;
}

/* 10. Navigation Background Color */
.navbar, .navbar-light, .navbar-expand-lg, #mainNavbar, .mobile-nav-drawer {
    background-color: var(--nav-bg-color) !important;
}

/* 11. Navigation Text Color */
.navbar-nav .nav-link, .navbar-nav .nav-link:visited, .mobile-nav-link, .navbar-brand {
    color: var(--nav-text-color) !important;
}

/* 12. Footer Background Color */
footer, .site-footer, .footer-section, #mainFooter {
    background-color: var(--footer-bg-color) !important;
}

/* 13. Footer Text Color */
footer, footer p, footer a, footer .text-muted, footer h5, .footer-text, .copyright-band {
    color: var(--footer-text-color) !important;
}
CSS;

            $cssDir = public_path('css');
            if (!is_dir($cssDir)) {
                mkdir($cssDir, 0755, true);
            }
            file_put_contents(public_path('css/theme.css'), $cssContent);
        } catch (\Exception $e) {
            Log::error('Descriptive CSS generation failed: ' . $e->getMessage());
        }
    }
}
