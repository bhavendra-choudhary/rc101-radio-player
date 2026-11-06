<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),
    'stream_url' => env('STREAM_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY', 'base64:enAxNWcxc2JzNTBpd294dWlkd2F3Y240OGt1b3h3eHk='),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Radio Settings
    |--------------------------------------------------------------------------
    */
    'social_share_image' => env('SOCIAL_SHARE_IMAGE'),
    'default_artwork_path' => env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg'),
    'slogan_file_path' => env('SLOGAN_FILE_PATH', 'config/slogan.txt'),
    'station_slogan' => env('STATION_SLOGAN', 'Your Favorite Live Radio Station'),
    'stream_url' => env('STREAM_URL', 'https://streams.radiomast.io/rc101'),

    /*
    |--------------------------------------------------------------------------
    | Station Format
    |--------------------------------------------------------------------------
    |
    | Controls format-driven content across the site: concerts, news, section
    | text and terminology. Change STATION_FORMAT in .env or via Admin Panel
    | to redeploy for any music format without touching source code.
    |
    */
    'station_format' => env('STATION_FORMAT', 'country'),

    'format_config' => [
        'country' => [
            'label'              => 'Country',
            'label_plural'       => 'Country',
            'concert_genre'      => 'country',
            'ticketmaster_genre' => 'Country',
            'news_keywords'      => 'country music',
            'concert_heading'    => 'Discover top Country music concerts happening',
            'news_section_title' => 'Country Music News',
            'concert_icon'       => 'fas fa-hat-cowboy',
        ],
        'pop' => [
            'label'              => 'Pop',
            'label_plural'       => 'Pop',
            'concert_genre'      => 'pop',
            'ticketmaster_genre' => 'Pop',
            'news_keywords'      => 'pop music hits chart',
            'concert_heading'    => 'Discover top Pop music concerts happening',
            'news_section_title' => 'Pop Music News',
            'concert_icon'       => 'fas fa-music',
        ],
        'rock' => [
            'label'              => 'Rock',
            'label_plural'       => 'Rock',
            'concert_genre'      => 'rock',
            'ticketmaster_genre' => 'Rock',
            'news_keywords'      => 'rock music band tour',
            'concert_heading'    => 'Discover top Rock music concerts happening',
            'news_section_title' => 'Rock Music News',
            'concert_icon'       => 'fas fa-guitar',
        ],
        'punk' => [
            'label'              => 'Punk',
            'label_plural'       => 'Punk',
            'concert_genre'      => 'punk',
            'ticketmaster_genre' => 'Punk',
            'news_keywords'      => 'punk rock alternative music tour',
            'concert_heading'    => 'Discover top Punk & Alternative rock concerts happening',
            'news_section_title' => 'Punk & Alt-Rock News',
            'concert_icon'       => 'fas fa-bolt',
        ],
        'jazz' => [
            'label'              => 'Jazz',
            'label_plural'       => 'Jazz',
            'concert_genre'      => 'jazz',
            'ticketmaster_genre' => 'Jazz',
            'news_keywords'      => 'jazz music festival performance',
            'concert_heading'    => 'Discover top Jazz concerts and live sessions happening',
            'news_section_title' => 'Jazz Music News',
            'concert_icon'       => 'fas fa-music',
        ],
        'hip-hop' => [
            'label'              => 'Hip-Hop',
            'label_plural'       => 'Hip-Hop',
            'concert_genre'      => 'hip-hop',
            'ticketmaster_genre' => 'Hip-Hop/Rap',
            'news_keywords'      => 'hip hop music rap artist tour',
            'concert_heading'    => 'Discover top Hip-Hop concerts happening',
            'news_section_title' => 'Hip-Hop Music News',
            'concert_icon'       => 'fas fa-microphone',
        ],
        'rnb' => [
            'label'              => 'R&B / Soul',
            'label_plural'       => 'R&B / Soul',
            'concert_genre'      => 'rnb',
            'ticketmaster_genre' => 'R&B',
            'news_keywords'      => 'rnb soul music artist tour',
            'concert_heading'    => 'Discover top R&B and Soul concerts happening',
            'news_section_title' => 'R&B & Soul News',
            'concert_icon'       => 'fas fa-heart',
        ],
        'metal' => [
            'label'              => 'Heavy Metal',
            'label_plural'       => 'Metal',
            'concert_genre'      => 'metal',
            'ticketmaster_genre' => 'Metal',
            'news_keywords'      => 'heavy metal rock band tour festival',
            'concert_heading'    => 'Discover top Metal concerts and festivals happening',
            'news_section_title' => 'Heavy Metal News',
            'concert_icon'       => 'fas fa-skull',
        ],
        'electronic' => [
            'label'              => 'Electronic / EDM',
            'label_plural'       => 'EDM',
            'concert_genre'      => 'electronic',
            'ticketmaster_genre' => 'Dance/Electronic',
            'news_keywords'      => 'edm electronic music dj festival dance',
            'concert_heading'    => 'Discover top Electronic & Dance events happening',
            'news_section_title' => 'Electronic Music News',
            'concert_icon'       => 'fas fa-headphones',
        ],
        'blues' => [
            'label'              => 'Blues',
            'label_plural'       => 'Blues',
            'concert_genre'      => 'blues',
            'ticketmaster_genre' => 'Blues',
            'news_keywords'      => 'blues music festival live tour',
            'concert_heading'    => 'Discover top Blues concerts happening',
            'news_section_title' => 'Blues Music News',
            'concert_icon'       => 'fas fa-guitar',
        ],
        'folk' => [
            'label'              => 'Folk / Americana',
            'label_plural'       => 'Folk / Americana',
            'concert_genre'      => 'folk',
            'ticketmaster_genre' => 'Folk',
            'news_keywords'      => 'folk americana bluegrass music tour',
            'concert_heading'    => 'Discover top Folk & Americana concerts happening',
            'news_section_title' => 'Folk & Americana News',
            'concert_icon'       => 'fas fa-tree',
        ],
        'classical' => [
            'label'              => 'Classical',
            'label_plural'       => 'Classical',
            'concert_genre'      => 'classical',
            'ticketmaster_genre' => 'Classical',
            'news_keywords'      => 'classical symphony orchestra concert',
            'concert_heading'    => 'Discover top Classical symphonies and performances happening',
            'news_section_title' => 'Classical Music News',
            'concert_icon'       => 'fas fa-compact-disc',
        ],
        'latin' => [
            'label'              => 'Latin',
            'label_plural'       => 'Latin',
            'concert_genre'      => 'latin',
            'ticketmaster_genre' => 'Latin',
            'news_keywords'      => 'latin music awards concert tour',
            'concert_heading'    => 'Discover top Latin music concerts happening',
            'news_section_title' => 'Latin Music News',
            'concert_icon'       => 'fas fa-fire',
        ],
        'reggae' => [
            'label'              => 'Reggae',
            'label_plural'       => 'Reggae',
            'concert_genre'      => 'reggae',
            'ticketmaster_genre' => 'Reggae',
            'news_keywords'      => 'reggae dub music festival',
            'concert_heading'    => 'Discover top Reggae concerts and festivals happening',
            'news_section_title' => 'Reggae Music News',
            'concert_icon'       => 'fas fa-sun',
        ],
        'gospel' => [
            'label'              => 'Gospel / Christian',
            'label_plural'       => 'Gospel',
            'concert_genre'      => 'gospel',
            'ticketmaster_genre' => 'Religious',
            'news_keywords'      => 'gospel christian contemporary music tour',
            'concert_heading'    => 'Discover top Gospel and Christian concerts happening',
            'news_section_title' => 'Gospel & Christian Music News',
            'concert_icon'       => 'fas fa-hands-helping',
        ],
    ],

];
