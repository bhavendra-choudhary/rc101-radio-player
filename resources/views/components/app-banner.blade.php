@php
    // Professional device detection
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isIOS = stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false;
    $isAndroid = stripos($userAgent, 'Android') !== false;
    $isMobile = $isIOS || $isAndroid;

    // External URL format & validation helper
    $formatStoreUrl = function (?string $url): ?string {
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

    $rawAppStore = \App\Models\SiteSetting::get('app_store_url');
    $rawPlayStore = \App\Models\SiteSetting::get('play_store_url');
    $iosStoreUrl = $formatStoreUrl($rawAppStore);
    $androidStoreUrl = $formatStoreUrl($rawPlayStore);

    $appStoreURL = $isIOS ? $iosStoreUrl : ($isAndroid ? $androidStoreUrl : ($iosStoreUrl ?: $androidStoreUrl));

    // Professional messaging
    $appName = \App\Models\SiteSetting::get('app_name', config('app.name', 'Radio Station'));
    $bannerTitle = \App\Models\SiteSetting::get('banner_title', $appName);
    $bannerSubtitle = \App\Models\SiteSetting::get('banner_subtitle', $isMobile ? 'Listen anywhere, anytime' : 'Get the app for the best experience');
    $buttonText = $isIOS ? 'Download on the App Store' : 
                   ($isAndroid ? 'Get it on Google Play' : 'Download App');

    // Generate unique banner ID for this session
    $bannerId = 'app-banner-' . uniqid();
@endphp

@if($isMobile && !empty($appStoreURL))
<!-- Professional App Download Banner -->
<div id="{{ $bannerId }}" class="professional-app-banner" data-banner-id="{{ $bannerId }}">
    <div class="app-banner-container">
        <div class="app-banner-logo">
            <div class="app-logo-wrapper">
                <i class="fas fa-broadcast-tower"></i>
            </div>
        </div>
        
        <div class="app-banner-content">
            <div class="app-banner-title">{{ $bannerTitle }}</div>
            <div class="app-banner-subtitle">{{ $bannerSubtitle }}</div>
        </div>
        
        <div class="app-banner-actions">
            <a href="{{ $appStoreURL }}" class="app-banner-cta" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-download"></i>
                <span class="cta-text">{{ $buttonText }}</span>
                <i class="fas fa-external-link-alt cta-icon"></i>
            </a>
            <button class="app-banner-dismiss" aria-label="Dismiss banner">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bannerId = '{{ $bannerId }}';
    const banner = document.getElementById(bannerId);
    if (!banner) return;
    
    const dismissBtn = banner.querySelector('.app-banner-dismiss');
    
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function() {
            banner.classList.add('hidden');
        });
    }
});
</script>
@endpush
@endif
