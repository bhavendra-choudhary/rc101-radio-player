<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
        <script>
        if (window.location.hash) {
            history.scrollRestoration = 'manual';
            window.scrollTo(0, 0);
        }
    </script>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    @php
        use App\Models\SiteSetting;
        use App\Models\Footer;

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        $footer = Footer::getFooter();
        $appName = SiteSetting::get('app_name', $footer->brand_name ?? config('app.name'));
        $tagline = SiteSetting::get('station_slogan', env('STATION_SLOGAN', 'YOUR FAVORITE LIVE RADIO STATION'));
        $requestButtonText = SiteSetting::get('front_request_button_text') ?: 'Request a Song';

        // ── 1. Title Resolution ──
        $configuredSeoTitle = SiteSetting::get('seo_title');
        $pageYieldTitle = trim($__env->yieldContent('title'));
        if (!empty($configuredSeoTitle)) {
            $pageTitle = $configuredSeoTitle;
        } elseif (!empty($pageYieldTitle) && $pageYieldTitle !== 'Radio Station | Listen Live') {
            $pageTitle = $pageYieldTitle;
        } else {
            $pageTitle = SiteSetting::get('website_title', !empty($tagline) ? $appName . ' — ' . $tagline : $appName);
        }

        // ── 2. Standard SEO Meta ──
        $metaDescription = SiteSetting::get('seo_description', !empty($tagline) ? 'Listen to ' . $appName . ' live online — ' . strtolower($tagline) : 'Listen to ' . $appName . ' live online.');
        $metaKeywords    = SiteSetting::get('seo_keywords', 'radio, live stream, music, hits');
        $canonicalUrl    = SiteSetting::get('canonical_url', url()->current());
        $robotsIndexing  = SiteSetting::get('robots_indexing', 'index, follow');

        // ── 3. Image URL Resolution Helper ──
        $resolveFullUrl = function($settingKey, $fallbackKey = null) use ($appName) {
            $path = SiteSetting::get($settingKey);
            if (empty($path) && $fallbackKey) {
                $path = SiteSetting::get($fallbackKey);
            }
            if (empty($path)) {
                $path = SiteSetting::get('seo_image', SiteSetting::get('logo_path', 'Landscape_Logo.png'));
            }
            if (empty($path)) {
                $path = 'Landscape_Logo.png';
            }
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return preg_replace('/^http:\/\//i', 'https://', $path);
            }
            return preg_replace('/^http:\/\//i', 'https://', asset(ltrim($path, '/')));
        };

        // ── 4. OpenGraph & Social Share Images ──
        $ogTitle        = SiteSetting::get('og_title') ?: $pageTitle;
        $ogDescription  = SiteSetting::get('og_description') ?: $metaDescription;
        $ogUrl          = $canonicalUrl;
        $ogSiteName     = $appName;

        $ogImage        = $resolveFullUrl('og_image', 'seo_image');
        $facebookImage  = $resolveFullUrl('facebook_share_image', 'og_image');
        $textShareImage = $resolveFullUrl('text_share_image', 'og_image');
        $twitterImage   = $resolveFullUrl('twitter_image', 'og_image');

        // ── 5. Twitter / X Card ──
        $twitterCard        = SiteSetting::get('twitter_card', 'summary_large_image');
        $twitterSite        = SiteSetting::get('twitter_site', '@' . str_replace(' ', '', $appName));
        $twitterTitle       = SiteSetting::get('twitter_title') ?: $ogTitle;
        $twitterDescription = SiteSetting::get('twitter_description') ?: $ogDescription;

        // ── 6. Favicon Resolution ──
        $siteFavicon = SiteSetting::get('favicon_path');
        if (!empty($siteFavicon) && file_exists(public_path($siteFavicon))) {
            $faviconUrl = asset($siteFavicon);
        } elseif (file_exists(public_path('favicon.ico'))) {
            $faviconUrl = asset('favicon.ico');
        } elseif (file_exists(public_path('favicon.png'))) {
            $faviconUrl = asset('favicon.png');
        } else {
            $faviconUrl = asset('Landscape_Logo.png');
        }

        // Image mime type detection
        $ogImageExt  = strtolower(pathinfo(parse_url($ogImage, PHP_URL_PATH), PATHINFO_EXTENSION));
        $ogImageType = match($ogImageExt) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            default => 'image/jpeg',
        };
    @endphp

    {{-- ── 1. Page Title ── --}}
    <title>{{ $pageTitle }}</title>

    {{-- ── 2. Standard SEO Meta ── --}}
    <meta name="description" content="{{ $metaDescription }}">
    @if(!empty($metaKeywords))
    <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="robots" content="{{ $robotsIndexing }}">

    {{-- ── 3. Favicon ── --}}
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">

    {{-- ── 4. Open Graph (Facebook / WhatsApp / iMessage / LinkedIn) ── --}}
    <meta property="og:type"             content="website">
    <meta property="og:site_name"        content="{{ $ogSiteName }}">
    <meta property="og:title"            content="{{ $ogTitle }}">
    <meta property="og:description"      content="{{ $ogDescription }}">
    <meta property="og:url"              content="{{ $ogUrl }}">
    <meta property="og:locale"           content="en_US">
    @if(env('FB_APP_ID'))
    <meta property="fb:app_id"           content="{{ env('FB_APP_ID') }}">
    @endif
    <meta property="og:image"            content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:type"       content="{{ $ogImageType }}">
    <meta property="og:image:width"      content="1200">
    <meta property="og:image:height"     content="630">
    <meta property="og:image:alt"        content="{{ $ogTitle }}">
    <meta property="fb:share_image"      content="{{ $facebookImage }}">

    {{-- ── 5. Text-Message / iMessage Sharing Metadata ── --}}
    <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
    <meta property="msapplication-TileImage" content="{{ $textShareImage }}">

    {{-- ── 6. Twitter / X Card ── --}}
    <meta name="twitter:card"        content="{{ $twitterCard }}">
    @if(!empty($twitterSite))
    <meta name="twitter:site"        content="{{ $twitterSite }}">
    @endif
    <meta name="twitter:title"       content="{{ $twitterTitle }}">
    <meta name="twitter:description" content="{{ $twitterDescription }}">
    <meta name="twitter:image"       content="{{ $twitterImage }}">
    <meta name="twitter:image:alt"   content="{{ $twitterTitle }}">

    {{-- ── 7. External CSS Stylesheets ── --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Righteous&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ file_exists(public_path('css/theme.css')) ? filemtime(public_path('css/theme.css')) : time() }}">

    @php
        $themeSettings = $footer->theme_settings ?? [];
        $primaryBlue   = $themeSettings['primary_blue']   ?? '#800400';
        $secondaryBlue = $themeSettings['secondary_blue'] ?? '#902a00';
        $accentBlue    = $themeSettings['accent_blue']    ?? '#2f3283';
        $accentOrange  = $themeSettings['accent_orange']  ?? '#c841a4';
        $accentTeal    = $themeSettings['accent_teal']    ?? '#2616a2';
        $successGreen  = $themeSettings['success_green']  ?? '#27ae60';
        $darkGray      = $themeSettings['dark_gray']      ?? '#ea7171'; // Footer background
        $warmWhite     = $themeSettings['warm_white']     ?? '#fafafa'; // Headings / Button Text / icons
        $lightGray     = $themeSettings['light_gray']     ?? '#000000'; // Light background sections
        $softYellow    = $themeSettings['soft_yellow']    ?? '#0004c5'; // Subtle yellow background areas
        $lightYellow   = $themeSettings['light_yellow']   ?? '#fefcf0'; // Lighter yellow background areas
        $mediumGray    = $themeSettings['medium_gray']    ?? '#dcdfe5'; // Medium gray text
        $textColor     = $themeSettings['text_color']     ?? '#920100'; // Primary text color
        $borderColor   = $themeSettings['border_color']   ?? '#ffda24'; // Border color
        $playerHeight  = $themeSettings['player_height']  ?? '80px';

        $hexToRgb = function($hex, $default = '128, 4, 0') {
            $clean = ltrim((string)($hex ?? ''), '#');
            if (strlen($clean) === 3) {
                return hexdec(str_repeat(substr($clean, 0, 1), 2)) . ', ' .
                       hexdec(str_repeat(substr($clean, 1, 1), 2)) . ', ' .
                       hexdec(str_repeat(substr($clean, 2, 1), 2));
            } elseif (strlen($clean) >= 6) {
                return hexdec(substr($clean, 0, 2)) . ', ' .
                       hexdec(substr($clean, 2, 2)) . ', ' .
                       hexdec(substr($clean, 4, 2));
            }
            return $default;
        };
        $primaryBlueRgb   = $hexToRgb($primaryBlue, '128, 4, 0');
        $secondaryBlueRgb = $hexToRgb($secondaryBlue, '144, 42, 0');
        $accentBlueRgb    = $hexToRgb($accentBlue, '47, 50, 131');
    @endphp

    <style>
        :root {
            /* Main Brand Colors (Theme Settings from Admin) */
            --primary-blue: {{ $primaryBlue }};
            --secondary-blue: {{ $secondaryBlue }};
            --accent-blue: {{ $accentBlue }};
            --primary-maroon: {{ $primaryBlue }};
            --primary-maroon-hover: {{ $secondaryBlue }};

            /* RGB Variants for Opacities */
            --primary-blue-rgb: {{ $primaryBlueRgb }};
            --secondary-blue-rgb: {{ $secondaryBlueRgb }};
            --accent-blue-rgb: {{ $accentBlueRgb }};

            /* Accent Colors */
            --accent-orange: {{ $accentOrange }};
            --accent-teal: {{ $accentTeal }};
            --success-green: {{ $successGreen }};

            /* Background Colors */
            --dark-gray: {{ $darkGray }};
            --footer-bg-color: {{ $darkGray }};
            --warm-white: {{ $warmWhite }};
            --light-gray: {{ $lightGray }};
            --soft-yellow: {{ $softYellow }};
            --light-yellow: {{ $lightYellow }};

            /* Text & Border Colors */
            --medium-gray: {{ $mediumGray }};
            --text-color: {{ $textColor }};
            --border-color: {{ $borderColor }};

            /* Player Settings */
            --player-height: {{ $playerHeight }};
            --player-bg-color: {{ $primaryBlue }};
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background-color: #ffffff;
            background-attachment: fixed;
            overflow-x: clip;
            margin: 0;
            line-height: 1.6;
            min-height: 100vh;
            scroll-behavior: smooth;
        }

        /* Buttons & Core Elements dynamically bound to Admin Theme Settings */
        .btn-primary {
            background-color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            color: var(--warm-white, #ffffff) !important;
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: var(--secondary-blue) !important;
            border-color: var(--secondary-blue) !important;
            color: var(--warm-white, #ffffff) !important;
        }

        .btn-outline-primary {
            border-color: var(--primary-blue) !important;
            color: var(--primary-blue) !important;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-primary:active {
            background-color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            color: var(--warm-white, #ffffff) !important;
        }

        .text-primary {
            color: var(--primary-blue) !important;
        }

        .bg-primary {
            background-color: var(--primary-blue) !important;
        }

        .badge.bg-primary {
            background-color: var(--primary-blue) !important;
            color: var(--warm-white, #ffffff) !important;
        }

        main {
            background-color: #ffffff;
            min-height: 50vh;
        }

        .brand-font {
            font-family: 'Righteous', cursive;
        }

        .frosted-glass {
            background-color: rgba(255, 255, 255, 0.15);
            /* Slightly transparent white */
            backdrop-filter: blur(10px);
            /* The main blur effect */
            border: 1px solid rgba(255, 255, 255, 0.2);
            /* Subtle border to enhance the effect */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Promo Slider */
        .promo-slider {
            height: 60px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            position: relative;
        }

        .slide-container {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.8s ease;
            color: var(--accent-blue);
            font-weight: 600;
            font-size: 1.2rem;
            text-align: center;
            padding: 0 20px;
        }

        .slide-container a {
            color: var(--accent-blue);
            text-decoration: none;
            border-bottom: 1px dashed var(--accent-blue);
            transition: all 0.3s ease;
        }

        .slide-container a:hover {
            color: var(--warm-white);
            border-bottom: 1px solid var(--warm-white);
        }

        .slide-indicators {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }

        .indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background-color: var(--accent-blue);
            transform: scale(1.1);
        }

        /* Header Styles — Sticky Desktop / Fixed Mobile (via custom.css) */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 6px 0;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.25s ease;
        }

        .navbar-brand-logo {
            height: 90px !important;
            max-height: 90px !important;
            width: auto !important;
            object-fit: contain !important;
            display: block !important;
            transition: all 0.25s ease !important;
        }

        @media (max-width: 991.98px) {
            .navbar {
                padding: 6px 0 !important;
            }
            .navbar-brand-logo {
                height: 72px !important;
                max-height: 72px !important;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand-logo {
                height: 64px !important;
                max-height: 64px !important;
            }
        }

        .navbar-brand .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--warm-white);
            font-size: 24px;
            font-weight: bold;
        }

        .navbar-brand .logo-text {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-blue);
            margin-left: 10px;
        }

        .nav-link {
            font-weight: 700;
            font-size: 16px;
            color: var(--text-color, #2c3e50) !important;
            transition: all 0.3s ease;
            position: relative;
            margin: 0 6px;
        }

        .nav-link i {
            color: var(--text-color, #2c3e50) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover,
        .nav-link:hover i,
        .nav-link.active,
        .nav-link.active i,
        .nav-item.active > .nav-link,
        .nav-item.active > .nav-link i,
        .navbar-nav .nav-link.active,
        .navbar-nav .nav-link.active i {
            color: var(--accent-blue, #2f3283) !important;
        }

        .nav-link:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .nav-link:focus:not(.active),
        .nav-link:focus:not(.active) i {
            color: var(--primary-blue, #800400) !important;
        }

        /* No underline indicator on nav links — color change only for active/hover */
        .nav-link::after {
            display: none !important;
            content: none !important;
        }

        /* Listen Live Button */
        .listen-live-btn {
            background: linear-gradient(90deg, #FFD700 0%, #FFA500 100%);
            color: #1E3C72;
            font-weight: 600;
            border: 2px solid #1E3C72;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 51, 255, 0.3);
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        .listen-live-btn:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.4);
            background: linear-gradient(90deg, #FFD700 0%, #FF8C00 100%);
            color: #1E3C72;
        }

        /* Download App Button */
        .download-app-btn {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }

        .download-app-btn:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            background: linear-gradient(90deg, #5a67d8 0%, #6b46c1 100%);
            color: white;
            text-decoration: none;
        }

        /* Hero Section */
        .hero {
            position: relative;
            padding: 100px 0;
            color: var(--warm-white);
            overflow: hidden;
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.90) 0%, rgba(60, 8, 230, 0.63) 100%),
                url('https://images.unsplash.com/photo-1507838153414-b4b713384a76?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center / cover;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,128L48,133.3C96,139,192,149,288,160C384,171,480,181,576,165.3C672,149,768,107,864,112C960,117,1056,171,1152,186.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
            opacity: 0.2;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            line-height: 1.2;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        /* Content Sections */

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .section-bg {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
        }

        .card {
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.06);
            transition: all 0.3s ease;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(44, 62, 80, 0.12);
            border-color: var(--accent-blue);
        }

        .card-img {
            height: 180px;
            background: linear-gradient(135deg, var(--primary-blue, #800400) 0%, var(--secondary-blue, #902a00) 50%, var(--primary-blue, #800400) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 4rem;
            overflow: hidden;
            position: relative;
            border-radius: 12px 12px 0 0;
        }

        .card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .card:hover .card-img img {
            transform: scale(1.05);
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        .card-content {
            padding: 25px;
        }

        .card h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: var(--primary-blue);
            font-weight: 600;
        }

        .btn-accent {
            background: var(--accent-blue);
            color: var(--warm-white);
            border-color: var(--accent-blue);
        }

        .btn-accent:hover {
            background: var(--accent-teal);
            color: var(--warm-white);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(22, 160, 133, 0.2);
        }

        /* Bootstrap Button Overrides */
        .btn-primary {
            background-color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            color: var(--warm-white, #ffffff) !important;
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: var(--secondary-blue) !important;
            border-color: var(--secondary-blue) !important;
            color: var(--warm-white, #ffffff) !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.2) !important;
        }

        .btn-outline-secondary {
            background-color: #ffffff !important;
            color: #4b5563 !important;
            border: 1px solid #d1d5db !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .btn-outline-secondary:hover {
            background-color: #f3f4f6 !important;
            color: #111827 !important;
            border-color: #9ca3af !important;
        }

        /* Load More Concerts Button — Clean, subtle, secondary outline */
        #load-more-concerts-btn {
            background-color: #ffffff !important;
            color: var(--primary-blue, #800400) !important;
            border: 1.5px solid var(--primary-blue, #800400) !important;
            font-weight: 600 !important;
            padding: 10px 28px !important;
            border-radius: 25px !important;
            transition: all 0.25s ease !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06) !important;
        }

        #load-more-concerts-btn:hover {
            background-color: var(--primary-blue, #800400) !important;
            color: var(--warm-white, #ffffff) !important;
            border-color: var(--primary-blue, #800400) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(128, 4, 0, 0.2) !important;
        }

        #play-to-listen-text {
            color: white;
            font-size: 1rem;
            font-weight: 600;
            text-align: left;
        }

        /* Form Controls */
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }

        .form-control:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15);
            outline: none;
        }

        /* Alert Styles */
        .alert-warning {
            background-color: rgba(230, 126, 34, 0.1);
            border-color: var(--accent-orange);
            color: var(--text-color);
            border-radius: 6px;
        }

        /* News Scroll Container */
        .news-scroll-container {
            max-height: 600px;
            overflow-y: auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 16px rgba(44, 62, 80, 0.1);
            position: relative;
        }

        /* Scroll fade effect at bottom */
        .news-scroll-container::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(transparent, rgba(255, 255, 255, 0.9));
            pointer-events: none;
            border-radius: 0 0 15px 15px;
        }

        /* Custom Scrollbar */
        .news-scroll-container::-webkit-scrollbar {
            width: 8px;
        }

        .news-scroll-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        .news-scroll-container::-webkit-scrollbar-thumb {
            background: var(--accent-blue);
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .news-scroll-container::-webkit-scrollbar-thumb:hover {
            background: var(--primary-blue);
        }

        /* Billboard Card Styles */
        .billboard-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 249, 250, 0.95) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(52, 152, 219, 0.2);
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.1);
        }

        .billboard-card .card-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--warm-white);
            border-bottom: none;
        }

        .billboard-song-item {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(52, 152, 219, 0.1);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 80px;
        }

        .billboard-song-item:hover {
            background: rgba(52, 152, 219, 0.1);
            border-color: var(--accent-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
        }

        .billboard-position {
            background: var(--accent-blue);
            color: var(--warm-white);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .billboard-song-title {
            font-weight: 600;
            color: var(--primary-blue);
            margin-bottom: 2px;
        }

        .billboard-artist {
            color: var(--medium-gray);
            font-size: 0.9rem;
        }

        .billboard-stats {
            font-size: 0.8rem;
            color: var(--medium-gray);
        }

        .billboard-container-scroll {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .billboard-container-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .billboard-container-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .billboard-container-scroll::-webkit-scrollbar-thumb {
            background: var(--accent-blue);
            border-radius: 3px;
        }

        .billboard-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
            margin-right: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .billboard-song-item:hover .billboard-thumbnail {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .billboard-thumbnail-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--warm-white);
            font-size: 1.5rem;
            margin-right: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* News Cards in Scroll Container */
        .news-scroll-container .card {
            margin-bottom: 0;
            transition: all 0.3s ease;
        }

        .news-scroll-container .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.15);
        }

        /* Contact Form */
        .contact-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .form-control {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-blue) !important;
            box-shadow: 0 0 0 0.25rem rgba(128, 4, 0, 0.18) !important;
            outline: none !important;
        }

        /* Global Theme-Based Focus Outlines */
        a:focus,
        a:focus-visible {
            outline: none;
        }

        :focus-visible {
            outline: 2px solid var(--accent-blue, #2f3283) !important;
            outline-offset: 2px !important;
        }

        .navbar a:focus,
        .navbar a:focus-visible {
            outline: none !important;
            color: var(--accent-blue, #2f3283) !important;
        }

        /* Footer */
        footer,
        .site-footer {
            background: var(--footer-bg-color, var(--dark-gray, #2c3e50)) !important;
            color: var(--warm-white, #ffffff) !important;
            padding: 60px 0 calc(var(--player-height) + 30px);
            border-top: 4px solid var(--primary-blue, #800400);
        }

        /* Force all footer text warm-white — overrides Bootstrap utilities */
        footer *,
        footer p,
        footer a,
        footer span,
        footer li,
        footer div,
        footer small,
        .site-footer *,
        .site-footer p,
        .site-footer a,
        .site-footer span {
            color: var(--warm-white, #ffffff) !important;
        }

        .footer-column h3,
        .footer-heading {
            color: var(--warm-white, #ffffff) !important;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 18px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3::after,
        .footer-heading::after {
            display: none !important;
            content: none !important;
        }

        .footer-column p,
        .footer-description {
            color: var(--warm-white, rgba(255, 255, 255, 0.92)) !important;
            font-weight: 400;
            font-size: 1rem;
            line-height: 1.65;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        /* Footer Links - Theme Based & Focus Styled */
        .footer-links a,
        .footer-quick-link {
            color: var(--warm-white, #ffffff) !important;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none !important;
            outline: none !important;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 3px 8px;
            margin-left: -8px;
            border-radius: 6px;
        }

        .footer-links a i,
        .footer-quick-link i,
        .footer-link-icon {
            color: var(--warm-white, rgba(255, 255, 255, 0.8)) !important;
            transition: all 0.25s ease;
        }

        /* State on Focus and Hover */
        .footer-links a:hover,
        .footer-links a:focus,
        .footer-links a:focus-visible,
        .footer-links a:active,
        .footer-quick-link:hover,
        .footer-quick-link:focus,
        .footer-quick-link:focus-visible,
        .footer-quick-link:active,
        footer a:hover,
        footer a:focus,
        footer a:focus-visible {
            color: var(--accent-blue, #2f3283) !important;
            background: transparent !important;
            text-decoration: none !important;
            outline: none !important;
            transform: none !important;
        }

        .footer-quick-link:focus-visible {
            box-shadow: 0 0 0 2px var(--border-color, #ffda24) !important;
        }

        .footer-links a:hover i,
        .footer-links a:focus i,
        .footer-links a:focus-visible i,
        .footer-quick-link:hover i,
        .footer-quick-link:focus i,
        .footer-quick-link:focus-visible i,
        .footer-quick-link:hover .footer-link-icon,
        .footer-quick-link:focus .footer-link-icon,
        .footer-quick-link:focus-visible .footer-link-icon {
            color: var(--accent-blue, #2f3283) !important;
            transform: none !important;
        }

        /* Footer Social Buttons */
        .social-links,
        .footer-social-wrapper {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }

        .social-links a,
        .footer-social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2) !important;
            color: var(--warm-white, #ffffff) !important;
            transition: all 0.25s ease;
            font-size: 16px;
            outline: none !important;
            text-decoration: none !important;
        }

        .social-links a:hover,
        .social-links a:focus,
        .social-links a:focus-visible,
        .footer-social-btn:hover,
        .footer-social-btn:focus,
        .footer-social-btn:focus-visible {
            background: var(--primary-blue, #800400) !important;
            color: var(--warm-white, #ffffff) !important;
            transform: translateY(-3px) scale(1.08) !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25), 0 0 0 2px var(--warm-white, #ffffff) !important;
            outline: none !important;
        }

        .copyright,
        .footer-copyright {
            color: var(--warm-white, rgba(255, 255, 255, 0.85)) !important;
            font-weight: 400;
            font-size: 0.95rem;
            text-align: center;
            padding-top: 20px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2) !important;
        }

        /* Animation Elements */
        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-15px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        /* Sticky Player */
        .sticky-player {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: var(--player-height);
            background: var(--player-background-color, #700c0e) !important;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 1000;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            transform: translateY(0);
        }

        .player-minimized {
            transform: translateY(calc(var(--player-height) - 20px));
        }

        .player-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .player-info {
            flex-grow: 1;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
            /* Allow text truncation */
            overflow: hidden;
            /* Mobile touch: cursor:pointer required for iOS Safari click events on divs */
            cursor: pointer;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            -webkit-user-select: none;
        }

        button.mini-player-icon,
        .mini-player-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #354298 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
            margin-right: 14px;
            font-size: 16px;
            border: none;
            outline: none;
            padding: 0;
            -webkit-appearance: none;
            appearance: none;
            cursor: pointer;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            -webkit-user-select: none;
            transform: translate3d(0, 0, 0);
            -webkit-transform: translate3d(0, 0, 0);
            position: relative;
            z-index: 1065;
        }

        button.mini-player-icon i,
        .mini-player-icon i,
        #mini-play-icon {
            pointer-events: none !important;
        }

        .player-title {
            font-weight: 600;
            font-size: 1rem;
            color: black;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .player-song {
            font-size: 0.9rem;
            color: var(--player-song-title-color, rgba(255, 255, 255, 0.9)) !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #current-title {
            font-weight: bold;
            text-transform: uppercase;
            color: var(--player-song-title-color, white) !important;
            font-size: 0.9rem;
        }

        #current-artist {
            font-style: italic;
            color: var(--player-artist-name-color, rgba(255, 255, 255, 0.9)) !important;
            font-size: 0.85rem;
        }

        .player-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .player-btn {
            background: transparent;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .player-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .minimize-btn {
            background: var(--accent-yellow);
            color: var(--primary-blue);
        }

        .minimize-btn:hover {
            background: var(--accent-yellow-alt);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero {
                padding: 60px 0;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .player-title,
            .player-song {
                font-size: 0.85rem;
            }

            .player-buttons {
                gap: 8px;
            }

            .player-btn {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }
        }

        /* App Download Modal Styling */
        #appDownloadModal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            overflow: hidden;
        }

        #appDownloadModal .modal-header {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #1E3C72 100%);
            color: white;
            padding: 20px 25px;
            border-bottom: none;
        }

        #appDownloadModal .modal-title {
            font-weight: 700;
            font-size: 1.3rem;
        }

        #appDownloadModal .btn-close {
            filter: brightness(0) invert(1);
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transition: all 0.3s ease;
        }

        #appDownloadModal .btn-close:hover {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1);
        }

        #appDownloadModal .btn-close:focus {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
            opacity: 1;
        }

        #appDownloadModal .custom-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            color: #333;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        #appDownloadModal .custom-close-btn:hover {
            background: rgba(255, 255, 255, 1);
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        #appDownloadModal .custom-close-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
        }

        #appDownloadModal .app-icon {
            width: 160px;
            height: 160px;
            margin: 0 auto;
            background: transparent;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
        }

        #appDownloadModal .app-icon i {
            color: white;
        }

        #appDownloadModal .modal-body h4 {
            color: var(--primary-blue);
            font-weight: 700;
            margin-bottom: 10px;
        }

        #appDownloadModal .modal-body p {
            color: var(--medium-gray);
            font-size: 0.95rem;
        }

        #appDownloadModal .btn-primary {
            background: linear-gradient(135deg, #1E3C72 0%, #2c3e50 50%, #3498db 100%);
            border: none;
            border-radius: 15px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        #appDownloadModal .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #1A2F5A 0%, #243447 50%, #2980b9 100%);
        }

        #appDownloadModal .btn-outline-primary {
            border: 2px solid #FFD700;
            color: #1E3C72;
            border-radius: 15px;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1rem;
            background: transparent;
            transition: all 0.3s ease;
        }

        #appDownloadModal .btn-outline-primary:hover {
            background: linear-gradient(135deg, #FFD700 0%, #FF8C00 50%, #FF6347 100%);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 140, 0, 0.3);
        }

        #appDownloadModal .btn-success {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border: none;
            border-radius: 15px;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.3);
            color: #1E3C72;
            transition: all 0.3s ease;
        }

        #appDownloadModal .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(255, 215, 0, 0.5);
            background: linear-gradient(135deg, #FFC700 0%, #FF8C00 100%);
        }

        /* Responsive modal */
        @media (max-width: 576px) {
            #appDownloadModal .modal-dialog {
                margin: 20px;
            }

            #appDownloadModal .modal-content {
                border-radius: 15px;
            }

            #appDownloadModal .modal-header {
                padding: 15px 20px;
            }

            #appDownloadModal .modal-body {
                padding: 25px 20px;
            }

            #appDownloadModal .app-icon {
                width: 60px;
                height: 60px;
            }

            #appDownloadModal .app-icon i {
                font-size: 2rem;
            }
        }

        /* Floating Request Button Styles */
        .floating-request-btn {
            position: fixed;
            bottom: calc(var(--player-height, 80px) + 20px);
            left: 50%;
            transform: translateX(-50%);
            z-index: 1040;
            transition: all 0.3s ease;
        }

        /* Mobile floating request button handled in custom.css */

        body.modal-open .floating-request-btn,
        body.modal-open #scroll-to-top,
        body.mobile-menu-open .floating-request-btn,
        body.mobile-menu-open #scroll-to-top {
            display: none !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .request-btn {
            background: linear-gradient(135deg, #FF416C, #FF4B2B);
            border: none;
            border-radius: 50px;
            color: white;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(255, 65, 108, 0.5);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            animation: request-pulse 1.5s infinite;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .request-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .request-btn:hover:before {
            left: 100%;
        }

        .request-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
            color: white;
        }

        .request-btn i {
            font-size: 15px;
        }

        @media(max-width: 390px) {
            .request-btn i {
                font-size: 12px;
            }

            .request-btn {
                padding: 12px 18px;
                font-size: 12px;
            }
        }

        @keyframes request-pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 65, 108, 0.7);
            }

            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 15px rgba(255, 65, 108, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 65, 108, 0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ file_exists(public_path('css/theme.css')) ? filemtime(public_path('css/theme.css')) : time() }}">
    <script src="https://cdn.jsdelivr.net/npm/htmx.org@1.9.12/dist/htmx.min.js"></script>
    <script>
        document.addEventListener("htmx:afterSettle", function(evt) {
            if (evt.detail.target.tagName && evt.detail.target.tagName.toLowerCase() === 'main') {
                window.document.dispatchEvent(new Event("DOMContentLoaded", {
                    bubbles: true,
                    cancelable: true
                }));
            }
        });
    </script>

    {{-- ── JSON-LD Structured Data (RadioStation + WebSite) — Google Rich Results ── --}}
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "RadioStation",
          "name": "{{ $ogSiteName }}",
          "description": "{{ $ogDescription }}",
          "url": "{{ config('app.url') }}",
          "logo": {
            "@type": "ImageObject",
            "url": "{{ $ogImage }}"
          },
          "image": "{{ $ogImage }}",
          "broadcastAffiliateOf": {
            "@type": "Organization",
            "name": "{{ $ogSiteName }}"
          }
        },
        {
          "@type": "WebSite",
          "name": "{{ $ogSiteName }}",
          "url": "{{ config('app.url') }}"
        }
      ]
    }
    </script>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ file_exists(public_path('css/custom.css')) ? filemtime(public_path('css/custom.css')) : time() }}">
    @stack('styles')
</head>

<body hx-boost="true" hx-target="#main-content" hx-select="#main-content" hx-swap="outerHTML">

    @include('partials.header') {{-- Shared header --}}

    <main id="main-content" hx-history-elt>
        @yield('content') {{-- Page-specific content --}}
    </main>

    @include('partials.footer') {{-- Shared footer --}}

    @include('partials.auth-modal') {{-- Shared Auth Modal (Sign Up & Log In) --}}

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    @include('layouts.partials.scripts-core-player')

    @include('layouts.partials.scripts-metadata')

    @include('layouts.partials.scripts-app-modals')




    <!-- Stack Scripts -->
    @stack('scripts')

    <!-- Contest Modal Component -->

    @if (!request()->routeIs('events.*'))
        <!-- Floating Request Button -->
        <div class="floating-request-btn">
            <button class="request-btn" id="openRequestModalGlobal" aria-label="{{ $requestButtonText }}">
                <i class="fas fa-headphones"></i> {{ $requestButtonText }}
            </button>
        </div>
    @endif

    <!-- Scroll To Top Button -->
    <button id="scroll-to-top" title="Back to top" aria-label="Scroll to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <style>
        #scroll-to-top {
            position: fixed;
            bottom: calc(var(--player-height, 75px) + env(safe-area-inset-bottom, 0px) + 14px);
            right: 20px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 4px 14px rgba(30, 60, 114, 0.45);
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transform: translateY(12px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s ease;
        }
        #scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        #scroll-to-top:hover {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-blue));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.55);
        }
    </style>
    <script>
        (function () {
            var btn = document.getElementById('scroll-to-top');
            if (!btn) return;
            window.addEventListener('scroll', function () {
                btn.classList.toggle('visible', window.scrollY > 300);
            }, { passive: true });
            btn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>

    <x-app-banner />

    <!-- Front-End Session Keepalive & Auto-Refresh Script -->
    <script>
        (function() {
            var keepAliveUrl = "{{ route('session.keepalive') }}";
            var lastPing = Date.now();

            function refreshFrontSession() {
                fetch(keepAliveUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    if (res.status === 419) {
                        window.location.reload();
                        return null;
                    }
                    return res.json();
                })
                .then(function(data) {
                    if (!data || !data.csrf_token) return;
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', data.csrf_token);
                    document.querySelectorAll('input[name="_token"]').forEach(function(inp) {
                        inp.value = data.csrf_token;
                    });
                    lastPing = Date.now();
                })
                .catch(function() {});
            }

            // Periodic heartbeat every 5 minutes (300000ms)
            setInterval(refreshFrontSession, 300000);

            // Refresh on focus / visibility return
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible' && (Date.now() - lastPing > 60000)) {
                    refreshFrontSession();
                }
            });
            window.addEventListener('focus', function() {
                if (Date.now() - lastPing > 60000) {
                    refreshFrontSession();
                }
            });
        })();
    </script>
</body>

</html>
