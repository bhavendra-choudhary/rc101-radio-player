@php
    $footer = App\Models\Footer::getFooter();

    // 1. Station Brand / App Name (dynamic, no hardcoded fallbacks)
    $configuredAppName = \App\Models\SiteSetting::get('app_name');
    if (empty($configuredAppName) || trim($configuredAppName) === '' || $configuredAppName === 'Radio Station') {
        $configuredAppName = ($footer && !empty($footer->brand_name) && $footer->brand_name !== 'Radio Station')
            ? $footer->brand_name
            : config('app.name', 'Radio Station');
    }
    $brandName = trim($configuredAppName);

    // 2. Station Description
    $defaultDesc = "The ultimate destination for music, news, concerts, and live events.";
    $rawDesc = \App\Models\SiteSetting::get('front_about_text', $footer->description ?? $defaultDesc);
    $description = (!empty($rawDesc) && !str_contains($rawDesc, "favorite source")) ? $rawDesc : ($footer->description ?? $defaultDesc);

    // 3. Copyright Text
    $rawCopy = \App\Models\SiteSetting::get('copyright_text', $footer->copyright_text ?? '');
    if (empty($rawCopy) || str_contains($rawCopy, 'Radio Station') || str_contains($rawCopy, 'Laravel')) {
        $copyrightText = $brandName . '. All rights reserved.';
    } else {
        $copyrightText = $rawCopy;
    }

    // 4. External URL Sanitization Helper (ensures valid https:// and ignores blanks / hashes)
    $formatExternalUrl = function (?string $url): ?string {
        if ($url === null) return null;
        $url = trim($url);
        if ($url === '' || $url === '#' || strtolower($url) === 'javascript:void(0)') {
            return null;
        }
        if (!preg_match('/^https?:\/\//i', $url)) {
            return 'https://' . ltrim($url, '/');
        }
        return $url;
    };

    // 5. Social Media URLs - Only display platforms with non-blank, valid URLs
    $socialPlatforms = [
        'facebook' => [
            'url' => $formatExternalUrl(\App\Models\SiteSetting::get('facebook_url', $footer->facebook_url ?? null)),
            'title' => 'Facebook',
            'icon' => 'fab fa-facebook-f',
        ],
        'instagram' => [
            'url' => $formatExternalUrl(\App\Models\SiteSetting::get('instagram_url', $footer->instagram_url ?? null)),
            'title' => 'Instagram',
            'icon' => 'fab fa-instagram',
        ],
        'twitter' => [
            'url' => $formatExternalUrl(\App\Models\SiteSetting::get('twitter_url', $footer->twitter_url ?? null)),
            'title' => 'X (Twitter)',
            'icon' => 'fab fa-twitter',
        ],
        'youtube' => [
            'url' => $formatExternalUrl(\App\Models\SiteSetting::get('youtube_url', $footer->youtube_url ?? null)),
            'title' => 'YouTube',
            'icon' => 'fab fa-youtube',
        ],
    ];

    $activeSocials = array_filter($socialPlatforms, fn($item) => !empty($item['url']));

    // 6. App Store URL (hidden if blank/null/whitespace/hash)
    $appStoreUrl = $formatExternalUrl(\App\Models\SiteSetting::get('app_store_url', $footer->app_store_url ?? null));

    // 7. Affiliate Links (merchandise store removed unless valid URL and label configured)
    $merchUrl = $formatExternalUrl(\App\Models\SiteSetting::get('affiliate_link_1_url'));
    $merchLabel = trim((string) \App\Models\SiteSetting::get('affiliate_link_1_label', ''));

    $aff2Url = $formatExternalUrl(\App\Models\SiteSetting::get('affiliate_link_2_url'));
    $aff2Label = trim((string) \App\Models\SiteSetting::get('affiliate_link_2_label', ''));

    $aff3Url = $formatExternalUrl(\App\Models\SiteSetting::get('affiliate_link_3_url'));
    $aff3Label = trim((string) \App\Models\SiteSetting::get('affiliate_link_3_label', ''));
@endphp

<!-- Footer -->
<footer class="site-footer" id="footer-section">
    <div class="container">
        <div class="row g-4 justify-content-between">

            <!-- Column 1: Branding & Social -->
            <div class="col-12 col-md-6 col-lg-5 text-start">
                <div class="footer-column">
                    <h4 class="footer-heading">{{ $brandName }}</h4>
                    <p class="footer-description">{{ $description }}</p>
                    @if(!empty($activeSocials))
                    <div class="footer-social-wrapper">
                        @foreach($activeSocials as $key => $social)
                        <a href="{{ $social['url'] }}" title="{{ $social['title'] }}" target="_blank" rel="noopener noreferrer" class="footer-social-btn">
                            <i class="{{ $social['icon'] }} footer-social-icon"></i>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- Column 2: Quick Links (Contact is directly underneath Quick Links, no separate header) -->
            <div class="col-12 col-md-5 col-lg-4 text-start">
                <div class="footer-column">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="list-unstyled footer-links-list mb-0 ps-0">
                        <li class="footer-link-item">
                            <a href="#" onclick="event.preventDefault(); window.scrollTo({top:0,behavior:'smooth'});" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> Home
                            </a>
                        </li>
                        <li class="footer-link-item">
                            <a href="#pop-culture-news" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> News
                            </a>
                        </li>
                        <li class="footer-link-item">
                            <a href="#concerts" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> Concerts
                            </a>
                        </li>
                        <li class="footer-link-item">
                            <a href="#events" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> Events
                            </a>
                        </li>
                        <li class="footer-link-item">
                            <a href="{{ route('home') }}#contact" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> Contact
                            </a>
                        </li>
                        @if(!empty($appStoreUrl))
                        <li class="footer-link-item">
                            <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener noreferrer" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> <i class="fab fa-apple me-1"></i> Download on App Store
                            </a>
                        </li>
                        @endif
                        @if(!empty($merchUrl) && !empty($merchLabel))
                        <li class="footer-link-item">
                            <a href="{{ $merchUrl }}" target="_blank" rel="noopener noreferrer" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> {{ $merchLabel }}
                            </a>
                        </li>
                        @endif
                        @if(!empty($aff2Url) && !empty($aff2Label))
                        <li class="footer-link-item">
                            <a href="{{ $aff2Url }}" target="_blank" rel="noopener noreferrer" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> {{ $aff2Label }}
                            </a>
                        </li>
                        @endif
                        @if(!empty($aff3Url) && !empty($aff3Label))
                        <li class="footer-link-item">
                            <a href="{{ $aff3Url }}" target="_blank" rel="noopener noreferrer" class="footer-quick-link">
                                <span class="footer-chevron">&rsaquo;</span> {{ $aff3Label }}
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

        </div>

        <div class="footer-copyright">
            &copy; {{ date('Y') }} {{ preg_replace('/^\d{4}\s*/', '', $copyrightText) }}
        </div>
    </div>
</footer>