@extends('admin.layout')

@section('title', 'Station Settings & Configuration - Admin')

@push('styles')
<style>
    .settings-nav .nav-link {
        color: #475569;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
    }
    .settings-nav .nav-link:hover {
        background-color: #e2e8f0;
        color: #1e293b;
    }
    .settings-nav .nav-link.active {
        background-color: #667eea;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    .settings-nav .nav-link i {
        width: 24px;
        font-size: 1.1rem;
        margin-right: 0.75rem;
    }
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .color-picker-preview {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        border: 2px solid #cbd5e1;
        cursor: pointer;
        padding: 0;
    }
    .card-settings-header {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white;
        border-top-left-radius: 12px !important;
        border-top-right-radius: 12px !important;
    }
    @media (max-width: 767.98px) {
        .settings-nav {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .settings-nav .nav-link {
            white-space: nowrap;
            margin-right: 0.5rem;
            margin-bottom: 0;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="fas fa-sliders-h text-primary me-2"></i>Station Configuration & Branding
            </h2>
            <p class="text-muted mb-0">Manage multi-station metadata, dynamic colors, artwork, API connections, and SEO.</p>
        </div>
        <button type="submit" form="stationSettingsForm" class="btn btn-primary btn-lg shadow-sm">
            <i class="fas fa-save me-2"></i>Save All Settings
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <h5 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following validation errors:</h5>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form id="stationSettingsForm" action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        
        <div class="row">
            <!-- Settings Navigation (Desktop Sticky / Mobile Scrollable) -->
            <div class="col-md-3 col-lg-3 mb-4">
                <div class="card border-0 shadow-sm sticky-md-top" style="top: 20px; z-index: 10;">
                    <div class="card-body p-3">
                        <div class="nav flex-column nav-pills settings-nav" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active" id="sec-station-tab" data-bs-toggle="pill" data-bs-target="#sec-station" type="button" role="tab"><i class="fas fa-radio"></i>1. Station</button>
                            <button class="nav-link" id="sec-branding-tab" data-bs-toggle="pill" data-bs-target="#sec-branding" type="button" role="tab"><i class="fas fa-paint-brush"></i>2. Branding</button>
                            <button class="nav-link" id="sec-stream-tab" data-bs-toggle="pill" data-bs-target="#sec-stream" type="button" role="tab"><i class="fas fa-broadcast-tower"></i>3. Stream Player</button>
                            <button class="nav-link" id="sec-artwork-tab" data-bs-toggle="pill" data-bs-target="#sec-artwork" type="button" role="tab"><i class="fas fa-compact-disc"></i>4. Artwork</button>
                            <button class="nav-link" id="sec-banners-tab" data-bs-toggle="pill" data-bs-target="#sec-banners" type="button" role="tab"><i class="fas fa-ad"></i>5. Banners</button>
                            <button class="nav-link" id="sec-news-tab" data-bs-toggle="pill" data-bs-target="#sec-news" type="button" role="tab"><i class="fas fa-newspaper"></i>6. News</button>
                            <button class="nav-link" id="sec-concerts-tab" data-bs-toggle="pill" data-bs-target="#sec-concerts" type="button" role="tab"><i class="fas fa-music"></i>7. Concerts</button>
                            <button class="nav-link" id="sec-website-tab" data-bs-toggle="pill" data-bs-target="#sec-website" type="button" role="tab"><i class="fas fa-globe"></i>8. Website</button>
                            <button class="nav-link" id="sec-colors-tab" data-bs-toggle="pill" data-bs-target="#sec-colors" type="button" role="tab"><i class="fas fa-palette"></i>9. Colors</button>
                            <button class="nav-link" id="sec-seo-tab" data-bs-toggle="pill" data-bs-target="#sec-seo" type="button" role="tab"><i class="fas fa-search"></i>10. SEO</button>
                            <button class="nav-link" id="sec-social-tab" data-bs-toggle="pill" data-bs-target="#sec-social" type="button" role="tab"><i class="fas fa-share-alt"></i>11. Social Sharing</button>
                            <button class="nav-link" id="sec-affiliate-tab" data-bs-toggle="pill" data-bs-target="#sec-affiliate" type="button" role="tab"><i class="fas fa-link"></i>12. Affiliate Links</button>
                            <button class="nav-link" id="sec-frontpage-tab" data-bs-toggle="pill" data-bs-target="#sec-frontpage" type="button" role="tab"><i class="fas fa-desktop"></i>13. Front Page</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Form Panels -->
            <div class="col-md-9 col-lg-9">
                <div class="tab-content" id="v-pills-tabContent">

                    <!-- SECTION 1: STATION -->
                    <div class="tab-pane fade show active" id="sec-station" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-radio me-2"></i>1. Station Configuration</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="app_name" class="form-label fw-bold">App / Station Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required>
                                        <div class="form-text">Active broadcast station brand name.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="station_format" class="form-label fw-bold"><i class="fas fa-compact-disc text-primary me-1"></i>Primary Music Genre / Station Format</label>
                                        <select class="form-select fw-semibold" id="station_format" name="station_format">
                                            @php
                                                $currentFormat = strtolower(old('station_format', $settings['station_format'] ?? 'country'));
                                                $formats = [
                                                    'country' => 'Country (Country Hits, Americana, Nashville)',
                                                    'pop' => 'Pop / Top 40 (Mainstream Pop Hits, Chart Toppers)',
                                                    'rock' => 'Rock / Classic Rock (Hard Rock, Alternative, Classic Hits)',
                                                    'punk' => 'Punk / Alternative (Pop Punk, Emo, Skate Punk)',
                                                    'jazz' => 'Jazz (Contemporary, Traditional, Smooth Jazz)',
                                                    'hip-hop' => 'Hip-Hop / Rap (Urban Contemporary, Rap, Trap)',
                                                    'rnb' => 'R&B / Soul (Contemporary R&B, Neo-Soul)',
                                                    'metal' => 'Heavy Metal (Metalcore, Thrash, Heavy Rock)',
                                                    'electronic' => 'Electronic / EDM (House, Techno, Festival EDM)',
                                                    'blues' => 'Blues (Delta, Chicago, Blues Rock)',
                                                    'folk' => 'Folk / Acoustic (Indie Folk, Singer-Songwriter)',
                                                    'classical' => 'Classical (Orchestral, Symphonic)',
                                                    'latin' => 'Latin (Reggaeton, Latin Pop, Salsa)',
                                                    'reggae' => 'Reggae / Dancehall',
                                                    'gospel' => 'Gospel / Christian Contemporary',
                                                ];
                                            @endphp
                                            @foreach($formats as $fKey => $fLabel)
                                                <option value="{{ $fKey }}" {{ $currentFormat === $fKey ? 'selected' : '' }}>{{ $fLabel }}</option>
                                            @endforeach
                                            @if(!array_key_exists($currentFormat, $formats))
                                                <option value="{{ $currentFormat }}" selected>Custom Genre: {{ ucfirst($currentFormat) }}</option>
                                            @endif
                                        </select>
                                        <div class="form-text text-primary"><i class="fas fa-info-circle me-1"></i>Instantly changes website text, news queries, concert genre filters, and category badges across the entire site without code edits.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="station_slogan" class="form-label fw-bold">Station Slogan</label>
                                        <input type="text" class="form-control" id="station_slogan" name="station_slogan" value="{{ old('station_slogan', $settings['station_slogan']) }}">
                                        <div class="form-text">Station tagline or slogan shown in title bar and player (Fallback configured in .env: <code>STATION_SLOGAN</code>).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="station_frequency" class="form-label fw-bold">Frequency / Broadcast Dial</label>
                                        <input type="text" class="form-control" id="station_frequency" name="station_frequency" value="{{ old('station_frequency', $settings['station_frequency']) }}">
                                        <div class="form-text">e.g. 98.5 FM, 101.3 FM, Online Radio</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="station_phone" class="form-label fw-bold">Studio Phone / Request Line</label>
                                        <input type="text" class="form-control" id="station_phone" name="station_phone" value="{{ old('station_phone', $settings['station_phone']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="station_email" class="form-label fw-bold">Contact Email</label>
                                        <input type="email" class="form-control" id="station_email" name="station_email" value="{{ old('station_email', $settings['station_email']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="website_url" class="form-label fw-bold">Main Website URL</label>
                                        <input type="url" class="form-control" id="website_url" name="website_url" value="{{ old('website_url', $settings['website_url']) }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="station_address" class="form-label fw-bold">Studio Location / Address</label>
                                        <input type="text" class="form-control" id="station_address" name="station_address" value="{{ old('station_address', $settings['station_address']) }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="external_urls" class="form-label fw-bold">External URLs / Mobile Web Stream Links</label>
                                        <textarea class="form-control" id="external_urls" name="external_urls" rows="2">{{ old('external_urls', $settings['external_urls']) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: BRANDING -->
                    <div class="tab-pane fade" id="sec-branding" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-paint-brush me-2"></i>2. Station Branding & Logos</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Station Logo</label>
                                        @if(!empty($settings['logo_path']) && file_exists(public_path($settings['logo_path'])))
                                            <div class="mb-2 p-3 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['logo_path']) }}" alt="Station Logo" style="max-height: 70px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                        <div class="form-text">
                                            <strong>Recommended Resolution:</strong> <code>512 &times; 128 px</code> (4:1 horizontal aspect ratio).<br>
                                            <strong>Format:</strong> Transparent PNG or SVG. Max file size: 2MB.
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Favicon</label>
                                        @if(!empty($settings['favicon_path']) && file_exists(public_path($settings['favicon_path'])))
                                            <div class="mb-2 p-3 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['favicon_path']) }}" alt="Favicon" style="height: 32px; width: 32px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="favicon" name="favicon" accept=".png,.ico,.jpg,.svg">
                                        <div class="form-text">
                                            <strong>Recommended Resolution:</strong> <code>32 &times; 32 px</code> or <code>64 &times; 64 px</code> (1:1 square).<br>
                                            <strong>Format:</strong> ICO or PNG. Max file size: 1MB.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: STREAM PLAYER -->
                    <div class="tab-pane fade" id="sec-stream" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-broadcast-tower me-2"></i>3. Live Stream Player Configuration</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="stream_url" class="form-label fw-bold">Primary Stream URL <span class="text-danger">*</span></label>
                                        <input type="url" class="form-control form-control-lg" id="stream_url" name="stream_url" value="{{ old('stream_url', $settings['stream_url']) }}" required>
                                        <div class="form-text">Direct audio stream URL (Icecast, Shoutcast, RadioMast, etc.).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="backup_stream_url" class="form-label fw-bold">Backup / Secondary Stream URL</label>
                                        <input type="url" class="form-control" id="backup_stream_url" name="backup_stream_url" value="{{ old('backup_stream_url', $settings['backup_stream_url']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="stream_mount_point" class="form-label fw-bold">Stream Mount Point</label>
                                        <input type="text" class="form-control" id="stream_mount_point" name="stream_mount_point" value="{{ old('stream_mount_point', $settings['stream_mount_point']) }}">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="autoplay_enabled" name="autoplay_enabled" value="1" {{ old('autoplay_enabled', $settings['autoplay_enabled']) == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="autoplay_enabled">Auto-play live stream on initial page load</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: ARTWORK -->
                    <div class="tab-pane fade" id="sec-artwork" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-compact-disc me-2"></i>4. Default Album Artwork & Fallback</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Default Station Album Artwork</label>
                                        @if(!empty($settings['default_artwork_path']) && file_exists(public_path($settings['default_artwork_path'])))
                                            <div class="mb-2 p-3 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['default_artwork_path']) }}" alt="Default Artwork" style="max-height: 120px; object-fit: contain;" class="rounded shadow-sm">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="default_artwork" name="default_artwork" accept="image/*">
                                        <div class="form-text">
                                            <strong>Recommended Resolution:</strong> <code>512 &times; 512 px</code> or <code>1024 &times; 1024 px</code> (1:1 square).<br>
                                            <strong>Format:</strong> High-resolution JPG or PNG (Max 2MB). Shown when current stream track artwork is unavailable.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fallback_artwork_url" class="form-label fw-bold">External Artwork Fallback URL</label>
                                        <input type="url" class="form-control" id="fallback_artwork_url" name="fallback_artwork_url" value="{{ old('fallback_artwork_url', $settings['fallback_artwork_url']) }}">
                                        <div class="form-text">Optional remote URL to fallback album cover art CDN or placeholder service.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: BANNERS -->
                    <div class="tab-pane fade" id="sec-banners" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-ad me-2"></i>5. Banners & App Download Links</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Header Promotional Banner Image</label>
                                        @if(!empty($settings['banner_image']) && file_exists(public_path($settings['banner_image'])))
                                            <div class="mb-2 p-2 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['banner_image']) }}" alt="Banner Image" style="max-height: 80px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="banner_image_file" name="banner_image_file" accept="image/*">
                                        <div class="form-text">
                                            <strong>Recommended Resolution:</strong> <code>1200 &times; 400 px</code> (3:1 wide ratio) or <code>1920 &times; 600 px</code>.<br>
                                            <strong>Format:</strong> JPG, PNG, or WebP. Max file size: 4MB.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="banner_destination_url" class="form-label fw-bold">Banner Target Destination URL</label>
                                        <input type="url" class="form-control" id="banner_destination_url" name="banner_destination_url" value="{{ old('banner_destination_url', $settings['banner_destination_url']) }}">
                                        <div class="form-text">URL to open when clicking the top banner (e.g. app download, concert promo, sponsor).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="banner_title" class="form-label fw-bold">App Banner Title</label>
                                        <input type="text" class="form-control" id="banner_title" name="banner_title" value="{{ old('banner_title', $settings['banner_title']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="banner_subtitle" class="form-label fw-bold">App Banner Subtitle</label>
                                        <input type="text" class="form-control" id="banner_subtitle" name="banner_subtitle" value="{{ old('banner_subtitle', $settings['banner_subtitle']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="app_store_url" class="form-label fw-bold"><i class="fab fa-apple me-1"></i>Apple App Store URL</label>
                                        <input type="url" class="form-control" id="app_store_url" name="app_store_url" value="{{ old('app_store_url', $settings['app_store_url']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="play_store_url" class="form-label fw-bold"><i class="fab fa-google-play me-1"></i>Google Play Store URL</label>
                                        <input type="url" class="form-control" id="play_store_url" name="play_store_url" value="{{ old('play_store_url', $settings['play_store_url']) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 6: NEWS -->
                    <div class="tab-pane fade" id="sec-news" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-newspaper me-2"></i>6. News API & Feed Settings</h5>
                            </div>
                            <div class="card-body p-4">
                                <!-- Staff Parameter & Query Tuning Guide Box -->
                                <div class="alert alert-info border-0 shadow-sm mb-4">
                                    <h6 class="fw-bold"><i class="fas fa-sliders-h me-2"></i>Staff Fine-Tuning Guide: News Query Strings & Parameters</h6>
                                    <p class="small mb-2">Our system automatically constructs the news queries and pulls from Google News RSS and NewsAPI. Use these exact parameters to fine-tune news content for any genre:</p>
                                    <div class="table-responsive bg-white rounded p-2 border mb-2">
                                        <table class="table table-sm table-borderless mb-0 small">
                                            <thead>
                                                <tr class="border-bottom">
                                                    <th>Genre</th>
                                                    <th>Recommended Query String Override (Optional)</th>
                                                    <th>Default Limit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td><strong>Country</strong></td><td><code>("country music" OR "Grand Ole Opry" OR "CMA" OR "Nashville") AND US</code></td><td><code>8 - 12</code></td></tr>
                                                <tr><td><strong>Pop</strong></td><td><code>("pop music" OR "Billboard Hot 100" OR "chart hits" OR "top 40") AND US</code></td><td><code>8 - 12</code></td></tr>
                                                <tr><td><strong>Rock</strong></td><td><code>("rock music" OR "rock band" OR "classic rock" OR "hard rock") AND US</code></td><td><code>8 - 12</code></td></tr>
                                                <tr><td><strong>Punk</strong></td><td><code>("punk rock" OR "pop punk" OR "alternative rock" OR "skate punk") AND US</code></td><td><code>8 - 12</code></td></tr>
                                                <tr><td><strong>Jazz</strong></td><td><code>("jazz music" OR "jazz festival" OR "smooth jazz" OR "blue note") AND US</code></td><td><code>8 - 12</code></td></tr>
                                                <tr><td><strong>Hip-Hop</strong></td><td><code>("hip hop" OR "rap music" OR "urban contemporary" OR "trap music") AND US</code></td><td><code>8 - 12</code></td></tr>
                                                <tr><td><strong>R&B / Soul</strong></td><td><code>("rnb music" OR "soul music" OR "contemporary rnb" OR "neo soul") AND US</code></td><td><code>8 - 12</code></td></tr>
                                                <tr><td><strong>Metal</strong></td><td><code>("heavy metal" OR "metalcore" OR "thrash metal" OR "metal festival") AND US</code></td><td><code>8 - 12</code></td></tr>
                                                <tr><td><strong>EDM</strong></td><td><code>("electronic dance music" OR "edm festival" OR "house music" OR "techno") AND US</code></td><td><code>8 - 12</code></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted"><i class="fas fa-lightbulb text-warning me-1"></i><strong>Tip:</strong> Leaving the custom query override empty allows the automated genre query builder to run based on the Primary Station Format.</small>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="news_api_key" class="form-label fw-bold">News API Key</label>
                                        <input type="password" class="form-control" id="news_api_key" name="news_api_key" value="{{ old('news_api_key', $settings['news_api_key']) }}" placeholder="••••••••••••••••">
                                        <div class="form-text">Optional API key from <a href="https://newsapi.org" target="_blank">newsapi.org</a> (System falls back to free Google News RSS if left blank).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="news_api_endpoint" class="form-label fw-bold">News API Base Endpoint</label>
                                        <input type="url" class="form-control" id="news_api_endpoint" name="news_api_endpoint" value="{{ old('news_api_endpoint', $settings['news_api_endpoint']) }}">
                                        <div class="form-text">Default: <code>https://newsapi.org/v2/</code></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="news_genre" class="form-label fw-bold">Station News Genre / Format</label>
                                        <select class="form-select" id="news_genre" name="news_genre">
                                            @php
                                                $currentNewsGenre = old('news_genre', $settings['news_genre'] ?? 'country');
                                                $newsGenres = [
                                                    'country' => 'Country / Americana',
                                                    'pop' => 'Pop / Top 40 Hits',
                                                    'rock' => 'Rock & Classic Rock',
                                                    'punk' => 'Punk & Alternative',
                                                    'jazz' => 'Jazz & Blues',
                                                    'hip-hop' => 'Hip-Hop & Rap',
                                                    'rnb' => 'R&B / Soul',
                                                    'metal' => 'Heavy Metal',
                                                    'electronic' => 'Electronic / EDM',
                                                    'custom' => 'Custom Query Override',
                                                ];
                                            @endphp
                                            @foreach($newsGenres as $gKey => $gLabel)
                                                <option value="{{ $gKey }}" {{ $currentNewsGenre === $gKey ? 'selected' : '' }}>{{ $gLabel }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Controls targeted automated query keywords.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="news_country" class="form-label fw-bold">Target Region / Country Code</label>
                                        <input type="text" class="form-control" id="news_country" name="news_country" value="{{ old('news_country', $settings['news_country']) }}" placeholder="us">
                                        <div class="form-text">ISO 2-letter country code: <code>us</code> (USA), <code>ca</code> (Canada), <code>gb</code> (UK).</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="news_items_limit" class="form-label fw-bold">Articles Display Limit (Number)</label>
                                        <input type="number" class="form-control" id="news_items_limit" name="news_items_limit" value="{{ old('news_items_limit', $settings['news_items_limit']) }}" min="1" max="50">
                                        <div class="form-text">Recommended: <code>8</code> to <code>12</code> articles. Maximum: <code>50</code>.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="default_news_source" class="form-label fw-bold">Default News Source Brand Name</label>
                                        <input type="text" class="form-control" id="default_news_source" name="default_news_source" value="{{ old('default_news_source', $settings['default_news_source']) }}">
                                        <div class="form-text">Shown when the original article source is anonymous (e.g. <em>RC 101 News</em>).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="news_search_query" class="form-label fw-bold">Custom Search Query Override (Optional)</label>
                                        <input type="text" class="form-control" id="news_search_query" name="news_search_query" value="{{ old('news_search_query', $settings['news_search_query']) }}" placeholder='e.g. ("country music" OR "Nashville") AND US'>
                                        <div class="form-text">Leave blank to allow the system to automatically target based on the selected genre.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 7: CONCERTS -->
                    <div class="tab-pane fade" id="sec-concerts" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-music me-2"></i>7. Concerts & Events Integration</h5>
                            </div>
                            <div class="card-body p-4">
                                <!-- Staff Ticketmaster Parameter Guide Box -->
                                <div class="alert alert-warning border-0 shadow-sm mb-4">
                                    <h6 class="fw-bold"><i class="fas fa-ticket-alt me-2"></i>Staff Fine-Tuning Guide: Ticketmaster Query Numbers & Parameters</h6>
                                    <p class="small mb-2">Our concert engine communicates with Ticketmaster's Discovery API v2 (<code>https://app.ticketmaster.com/discovery/v2/events.json</code>). Station staff can fine-tune query parameters for any musical genre using the reference table and parameter guidelines below:</p>
                                    
                                    <div class="table-responsive my-2">
                                        <table class="table table-sm table-bordered bg-white mb-2 small">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Station Format</th>
                                                    <th>Ticketmaster <code>classificationName</code></th>
                                                    <th>Default Radius</th>
                                                    <th>Batch Size</th>
                                                    <th>Sort &amp; Date Range</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>Country</strong></td>
                                                    <td><code>Country</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Pop</strong></td>
                                                    <td><code>Pop</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Rock</strong></td>
                                                    <td><code>Rock</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Punk</strong></td>
                                                    <td><code>Alternative Rock</code> / <code>Punk</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Jazz</strong></td>
                                                    <td><code>Jazz</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Hip-Hop</strong></td>
                                                    <td><code>Hip-Hop/Rap</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>R&B / Soul</strong></td>
                                                    <td><code>R&B</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Metal</strong></td>
                                                    <td><code>Metal</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>EDM / Dance</strong></td>
                                                    <td><code>Dance/Electronic</code></td>
                                                    <td><code>50</code> miles</td>
                                                    <td><code>100</code>/req (2 pages = 200)</td>
                                                    <td><code>date,asc</code> (Today &rarr; +12 mo)</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <ul class="small mb-0 ps-3">
                                        <li><strong>Radius Parameter (<code>radius</code>):</strong> Set to <code>50</code> miles. Ticketmaster strictly searches within this radius around the listener's GPS coordinates (or default fallback city).</li>
                                        <li><strong>API Fetch Batch Size (<code>size</code>):</strong> Set to <code>100</code> events per request (API maximum). In high-density markets (e.g., Nashville/Chicago), the system automatically fetches page 1 and page 2 to ensure no headline tour dates are missed.</li>
                                        <li><strong>Date Range (<code>startDateTime</code> &amp; <code>endDateTime</code>):</strong> Starts dynamically from today (<code>{{ date('Y-m-d') }}T00:00:00Z</code>) to <code>12 months</code> in the future. Past concerts are excluded automatically.</li>
                                        <li><strong>Sorting (<code>sort</code>):</strong> <code>date,asc</code> ensures chronological ordering with the earliest upcoming shows appearing first.</li>
                                    </ul>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="concerts_api_key" class="form-label fw-bold">Ticketmaster Discovery API Key</label>
                                        <input type="password" class="form-control" id="concerts_api_key" name="concerts_api_key" value="{{ old('concerts_api_key', $settings['concerts_api_key']) }}" placeholder="••••••••••••••••">
                                        <div class="form-text">Free API key from <a href="https://developer.ticketmaster.com" target="_blank">developer.ticketmaster.com</a> (5,000 queries/day).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="concerts_default_location" class="form-label fw-bold">Default Fallback City / Location Name</label>
                                        <input type="text" class="form-control" id="concerts_default_location" name="concerts_default_location" value="{{ old('concerts_default_location', $settings['concerts_default_location']) }}" placeholder="e.g. Nashville, TN or Los Angeles, CA">
                                        <div class="form-text">Used if the visitor denies GPS browser permissions (e.g. <em>Nashville, TN</em>, <em>Austin, TX</em>, <em>Chicago, IL</em>).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="concerts_radius" class="form-label fw-bold">Search Radius in Miles (Number)</label>
                                        <input type="number" class="form-control" id="concerts_radius" name="concerts_radius" value="{{ old('concerts_radius', $settings['concerts_radius']) }}" min="1" max="50">
                                        <div class="form-text">Recommended: <code>50</code> miles. (Maximum allowed by station policy: <code>50</code> miles).</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 8: WEBSITE -->
                    <div class="tab-pane fade" id="sec-website" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-globe me-2"></i>8. Website Global Content</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="website_title" class="form-label fw-bold">Website Header Title</label>
                                        <input type="text" class="form-control" id="website_title" name="website_title" value="{{ old('website_title', $settings['website_title']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="website_subtitle" class="form-label fw-bold">Website Subtitle / Headline</label>
                                        <input type="text" class="form-control" id="website_subtitle" name="website_subtitle" value="{{ old('website_subtitle', $settings['website_subtitle']) }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="copyright_text" class="form-label fw-bold">Footer Copyright Notice</label>
                                        <input type="text" class="form-control" id="copyright_text" name="copyright_text" value="{{ old('copyright_text', $settings['copyright_text']) }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="maintenance_notice" class="form-label fw-bold">System Maintenance Notice Banner</label>
                                        <textarea class="form-control" id="maintenance_notice" name="maintenance_notice" rows="2" placeholder="Leave empty if system is operating normally">{{ old('maintenance_notice', $settings['maintenance_notice']) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 9: COLORS (Descriptive Controls with Full Itemized UI Mapping) -->
                    <div class="tab-pane fade" id="sec-colors" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-palette me-2"></i>9. Dynamic Website &amp; Player Colors</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="alert alert-info border-0 shadow-sm mb-4">
                                    <h6 class="fw-bold mb-1"><i class="fas fa-info-circle me-2"></i>Staff Color Scheme Guidance &amp; Live Synchronization</h6>
                                    <p class="small mb-0">Every color field below controls a designated set of elements across the station website and audio player. <strong>Every single item affected by each color is explicitly itemized below.</strong> Changes take effect immediately across all desktop, tablet, and mobile views upon clicking <em>Save All Station Settings</em>.</p>
                                </div>
                                
                                <div class="row g-4">
                                    <!-- Group 1: Media Player Element Colors -->
                                    <div class="col-12">
                                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-2"><i class="fas fa-play-circle me-2"></i>1. Audio Player Element Colors (Sticky Player &amp; Car / Fullscreen Mode)</h6>
                                    </div>

                                    <!-- 1. player_song_title_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="player_song_title_color" class="form-label fw-bold text-dark mb-1">Media-Player Song-Title Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_player_song_title_color" value="{{ old('player_song_title_color', $settings['player_song_title_color']) }}" oninput="document.getElementById('player_song_title_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="player_song_title_color" name="player_song_title_color" value="{{ old('player_song_title_color', $settings['player_song_title_color']) }}" oninput="document.getElementById('picker_player_song_title_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Sticky bottom player song title text</li>
                                                    <li>Car Mode &amp; Fullscreen player track title heading</li>
                                                    <li>Live On-Air hero player current track title</li>
                                                    <li>Top mini-player track title display</li>
                                                    <li>Desktop &amp; mobile now-playing song title</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. player_artist_name_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="player_artist_name_color" class="form-label fw-bold text-dark mb-1">Media-Player Artist-Name Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_player_artist_name_color" value="{{ old('player_artist_name_color', $settings['player_artist_name_color']) }}" oninput="document.getElementById('player_artist_name_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="player_artist_name_color" name="player_artist_name_color" value="{{ old('player_artist_name_color', $settings['player_artist_name_color']) }}" oninput="document.getElementById('picker_player_artist_name_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Sticky bottom player artist name text</li>
                                                    <li>Car Mode &amp; Fullscreen player artist subtitle</li>
                                                    <li>Live On-Air hero player artist name line</li>
                                                    <li>Top mini-player artist display</li>
                                                    <li>Desktop &amp; mobile now-playing artist name</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. player_background_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="player_background_color" class="form-label fw-bold text-dark mb-1">Media-Player Background Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_player_background_color" value="{{ old('player_background_color', $settings['player_background_color']) }}" oninput="document.getElementById('player_background_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="player_background_color" name="player_background_color" value="{{ old('player_background_color', $settings['player_background_color']) }}" oninput="document.getElementById('picker_player_background_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Sticky bottom fixed audio player bar container</li>
                                                    <li>Car Mode player background container</li>
                                                    <li>Floating compact audio player dock container</li>
                                                    <li>Hero live stream player widget background</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Group 2: Typography & Content Colors -->
                                    <div class="col-12 mt-4">
                                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-2"><i class="fas fa-font me-2"></i>2. Website Typography &amp; Action Colors</h6>
                                    </div>

                                    <!-- 4. main_text_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="main_text_color" class="form-label fw-bold text-dark mb-1">Main Website Text Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_main_text_color" value="{{ old('main_text_color', $settings['main_text_color']) }}" oninput="document.getElementById('main_text_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="main_text_color" name="main_text_color" value="{{ old('main_text_color', $settings['main_text_color']) }}" oninput="document.getElementById('picker_main_text_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Standard website body copy across all pages (<code>body</code>, <code>p</code>)</li>
                                                    <li>News card article summaries &amp; excerpt descriptions</li>
                                                    <li>Concert tour dates, venue metadata &amp; city descriptions</li>
                                                    <li>Contest descriptions, rules &amp; entry instructions</li>
                                                    <li>Station contact info, street address &amp; office details</li>
                                                    <li>About Station narrative text &amp; DJ profile bios</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 5. heading_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="heading_color" class="form-label fw-bold text-dark mb-1">Heading Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_heading_color" value="{{ old('heading_color', $settings['heading_color']) }}" oninput="document.getElementById('heading_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="heading_color" name="heading_color" value="{{ old('heading_color', $settings['heading_color']) }}" oninput="document.getElementById('picker_heading_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>All section headings (<code>h1</code> through <code>h6</code>)</li>
                                                    <li>Section titles ("Country News", "Upcoming Concerts", "Meet the DJs")</li>
                                                    <li>News article headlines &amp; card titles</li>
                                                    <li>Concert tour names &amp; artist headings on cards</li>
                                                    <li>Modal popup headers &amp; alert titles</li>
                                                    <li>Hero section headline banner text</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 6. link_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="link_color" class="form-label fw-bold text-dark mb-1">Link Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_link_color" value="{{ old('link_color', $settings['link_color']) }}" oninput="document.getElementById('link_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="link_color" name="link_color" value="{{ old('link_color', $settings['link_color']) }}" oninput="document.getElementById('picker_link_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Hyperlinks throughout news articles &amp; text (<code>a</code>)</li>
                                                    <li>"Read Full Story" links on news cards</li>
                                                    <li>"Buy Tickets" &amp; "More Concert Info" ticket links</li>
                                                    <li>Station affiliate links &amp; sponsor URLs</li>
                                                    <li>Footer secondary links and social handles</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 7. button_bg_color -->
                                    <div class="col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="button_bg_color" class="form-label fw-bold text-dark mb-1">Button Background Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_button_bg_color" value="{{ old('button_bg_color', $settings['button_bg_color']) }}" oninput="document.getElementById('button_bg_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="button_bg_color" name="button_bg_color" value="{{ old('button_bg_color', $settings['button_bg_color']) }}" oninput="document.getElementById('picker_button_bg_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Primary action buttons (<code>.btn-primary</code>)</li>
                                                    <li>Live stream Play / Pause circular toggle buttons</li>
                                                    <li>"Listen Live" hero call-to-action button</li>
                                                    <li>"Song Request" submit button</li>
                                                    <li>"Load More Concerts" &amp; "Load More News" buttons</li>
                                                    <li>Contact form "Send Message" submit button</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 8. button_text_color -->
                                    <div class="col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="button_text_color" class="form-label fw-bold text-dark mb-1">Button Text Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_button_text_color" value="{{ old('button_text_color', $settings['button_text_color']) }}" oninput="document.getElementById('button_text_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="button_text_color" name="button_text_color" value="{{ old('button_text_color', $settings['button_text_color']) }}" oninput="document.getElementById('picker_button_text_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Text labels inside primary action buttons</li>
                                                    <li>Play / Pause icon inside circular buttons</li>
                                                    <li>"Submit Request" button text &amp; icons</li>
                                                    <li>"Buy Tickets" button label text</li>
                                                    <li>Contact form submit button text &amp; icons</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Group 3: Header, Navigation & Footer Colors -->
                                    <div class="col-12 mt-4">
                                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-2"><i class="fas fa-layer-group me-2"></i>3. Navigation, Header &amp; Footer Colors</h6>
                                    </div>

                                    <!-- 9. header_bg_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="header_bg_color" class="form-label fw-bold text-dark mb-1">Header Background Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_header_bg_color" value="{{ old('header_bg_color', $settings['header_bg_color']) }}" oninput="document.getElementById('header_bg_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="header_bg_color" name="header_bg_color" value="{{ old('header_bg_color', $settings['header_bg_color']) }}" oninput="document.getElementById('picker_header_bg_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Top site header wrapper container</li>
                                                    <li>Announcement banner / breaking news ticker bar</li>
                                                    <li>Station frequency &amp; call-sign utility top strip</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 10. nav_bg_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="nav_bg_color" class="form-label fw-bold text-dark mb-1">Navigation Background Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_nav_bg_color" value="{{ old('nav_bg_color', $settings['nav_bg_color']) }}" oninput="document.getElementById('nav_bg_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="nav_bg_color" name="nav_bg_color" value="{{ old('nav_bg_color', $settings['nav_bg_color']) }}" oninput="document.getElementById('picker_nav_bg_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Main desktop navigation bar (<code>nav.navbar</code>)</li>
                                                    <li>Mobile sliding drawer navigation menu background</li>
                                                    <li>Sticky navigation bar when scrolled</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 11. nav_text_color -->
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="nav_text_color" class="form-label fw-bold text-dark mb-1">Navigation Text Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_nav_text_color" value="{{ old('nav_text_color', $settings['nav_text_color']) }}" oninput="document.getElementById('nav_text_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="nav_text_color" name="nav_text_color" value="{{ old('nav_text_color', $settings['nav_text_color']) }}" oninput="document.getElementById('picker_nav_text_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Main menu links ("Home", "Concerts", "News", "Schedule", "Contact")</li>
                                                    <li>Station brand title / call-sign in navbar</li>
                                                    <li>Mobile menu navigation links</li>
                                                    <li>Active &amp; hovered menu navigation items</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 12. footer_bg_color -->
                                    <div class="col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="footer_bg_color" class="form-label fw-bold text-dark mb-1">Footer Background Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_footer_bg_color" value="{{ old('footer_bg_color', $settings['footer_bg_color']) }}" oninput="document.getElementById('footer_bg_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="footer_bg_color" name="footer_bg_color" value="{{ old('footer_bg_color', $settings['footer_bg_color']) }}" oninput="document.getElementById('picker_footer_bg_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Full-width bottom website footer container</li>
                                                    <li>Footer station brand, address &amp; contact background</li>
                                                    <li>Copyright and legal disclaimer band background</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 13. footer_text_color -->
                                    <div class="col-md-6">
                                        <div class="card h-100 border p-3 bg-light shadow-sm">
                                            <label for="footer_text_color" class="form-label fw-bold text-dark mb-1">Footer Text Color</label>
                                            <div class="color-picker-wrapper mb-2">
                                                <input type="color" class="color-picker-preview" id="picker_footer_text_color" value="{{ old('footer_text_color', $settings['footer_text_color']) }}" oninput="document.getElementById('footer_text_color').value = this.value">
                                                <input type="text" class="form-control font-monospace" id="footer_text_color" name="footer_text_color" value="{{ old('footer_text_color', $settings['footer_text_color']) }}" oninput="document.getElementById('picker_footer_text_color').value = this.value" required>
                                            </div>
                                            <div class="bg-white border rounded p-2 mt-auto">
                                                <span class="badge bg-secondary mb-1">Items Changed by this Color:</span>
                                                <ul class="small text-muted mb-0 ps-3">
                                                    <li>Footer station description &amp; mission statement paragraph</li>
                                                    <li>Station contact info (phone, email, frequency, address) in footer</li>
                                                    <li>Footer navigation links &amp; social icons</li>
                                                    <li>Copyright notice text &amp; developer attribution</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 10: SEO -->
                    <div class="tab-pane fade" id="sec-seo" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-search me-2"></i>10. Search Engine Optimization (SEO) & Indexing</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="seo_title" class="form-label fw-bold">Meta Title Tag</label>
                                        <input type="text" class="form-control" id="seo_title" name="seo_title" value="{{ old('seo_title', $settings['seo_title']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="canonical_url" class="form-label fw-bold">Canonical URL</label>
                                        <input type="url" class="form-control" id="canonical_url" name="canonical_url" value="{{ old('canonical_url', $settings['canonical_url']) }}" placeholder="https://yourstation.com">
                                    </div>
                                    <div class="col-12">
                                        <label for="seo_description" class="form-label fw-bold">Meta Description Tag</label>
                                        <textarea class="form-control" id="seo_description" name="seo_description" rows="2">{{ old('seo_description', $settings['seo_description']) }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="seo_keywords" class="form-label fw-bold">Meta Keywords</label>
                                        <input type="text" class="form-control" id="seo_keywords" name="seo_keywords" value="{{ old('seo_keywords', $settings['seo_keywords']) }}" placeholder="e.g. radio, live music, country hits">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="robots_indexing" class="form-label fw-bold">Search Engine Indexing Control (robots)</label>
                                        <select class="form-select" id="robots_indexing" name="robots_indexing">
                                            <option value="index, follow" {{ old('robots_indexing', $settings['robots_indexing']) == 'index, follow' ? 'selected' : '' }}>Index & Follow (Default / Production)</option>
                                            <option value="noindex, nofollow" {{ old('robots_indexing', $settings['robots_indexing']) == 'noindex, nofollow' ? 'selected' : '' }}>NoIndex & NoFollow (Staging / Private)</option>
                                            <option value="index, nofollow" {{ old('robots_indexing', $settings['robots_indexing']) == 'index, nofollow' ? 'selected' : '' }}>Index, NoFollow</option>
                                            <option value="noindex, follow" {{ old('robots_indexing', $settings['robots_indexing']) == 'noindex, follow' ? 'selected' : '' }}>NoIndex, Follow</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Default SEO Social Sharing Preview Image</label>
                                        @if(!empty($settings['seo_image']) && file_exists(public_path($settings['seo_image'])))
                                            <div class="mb-2 p-2 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['seo_image']) }}" alt="SEO Image" style="max-height: 100px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="seo_image_file" name="seo_image_file" accept="image/*">
                                        <div class="form-text">
                                            <strong>Recommended Resolution:</strong> <code>1200 &times; 630 px</code> (1.91:1 ratio for Facebook, Twitter, WhatsApp & Google preview cards).<br>
                                            <strong>Format:</strong> High quality JPG or PNG. Max file size: 2MB.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 11: SOCIAL SHARING & OPEN GRAPH -->
                    <div class="tab-pane fade" id="sec-social" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-share-alt me-2"></i>11. Social Media Links & Open Graph Metadata</h5>
                            </div>
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-globe me-2"></i>Social Profiles</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="facebook_url" class="form-label fw-bold"><i class="fab fa-facebook text-primary me-2"></i>Facebook Page URL</label>
                                        <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="{{ old('facebook_url', $settings['facebook_url']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="instagram_url" class="form-label fw-bold"><i class="fab fa-instagram text-danger me-2"></i>Instagram Profile URL</label>
                                        <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $settings['instagram_url']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="twitter_url" class="form-label fw-bold"><i class="fab fa-twitter text-info me-2"></i>X / Twitter Profile URL</label>
                                        <input type="url" class="form-control" id="twitter_url" name="twitter_url" value="{{ old('twitter_url', $settings['twitter_url']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="youtube_url" class="form-label fw-bold"><i class="fab fa-youtube text-danger me-2"></i>YouTube Channel URL</label>
                                        <input type="url" class="form-control" id="youtube_url" name="youtube_url" value="{{ old('youtube_url', $settings['youtube_url']) }}">
                                    </div>
                                </div>

                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-icons me-2"></i>Open Graph (Facebook / LinkedIn) Specific Metadata</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="og_title" class="form-label fw-bold">Open Graph Title</label>
                                        <input type="text" class="form-control" id="og_title" name="og_title" value="{{ old('og_title', $settings['og_title']) }}" placeholder="Fallback: Meta Title">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="og_description" class="form-label fw-bold">Open Graph Description</label>
                                        <input type="text" class="form-control" id="og_description" name="og_description" value="{{ old('og_description', $settings['og_description']) }}" placeholder="Fallback: Meta Description">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Open Graph Image (og:image)</label>
                                        @if(!empty($settings['og_image']) && file_exists(public_path($settings['og_image'])))
                                            <div class="mb-2 p-2 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['og_image']) }}" alt="OG Image" style="max-height: 80px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="og_image_file" name="og_image_file" accept="image/*">
                                        <div class="form-text"><strong>Recommended Resolution:</strong> <code>1200 &times; 630 px</code>.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Facebook Share Image</label>
                                        @if(!empty($settings['facebook_share_image']) && file_exists(public_path($settings['facebook_share_image'])))
                                            <div class="mb-2 p-2 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['facebook_share_image']) }}" alt="Facebook Image" style="max-height: 80px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="facebook_share_image_file" name="facebook_share_image_file" accept="image/*">
                                        <div class="form-text"><strong>Recommended Resolution:</strong> <code>1200 &times; 630 px</code>.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Text Share Image (iMessage)</label>
                                        @if(!empty($settings['text_share_image']) && file_exists(public_path($settings['text_share_image'])))
                                            <div class="mb-2 p-2 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['text_share_image']) }}" alt="Text Share Image" style="max-height: 80px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="text_share_image_file" name="text_share_image_file" accept="image/*">
                                        <div class="form-text"><strong>Recommended Resolution:</strong> <code>1200 &times; 630 px</code> or <code>600 &times; 600 px</code>.</div>
                                    </div>
                                </div>

                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fab fa-twitter me-2"></i>X / Twitter Card Metadata</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="twitter_card" class="form-label fw-bold">Twitter Card Type</label>
                                        <select class="form-select" id="twitter_card" name="twitter_card">
                                            <option value="summary_large_image" {{ old('twitter_card', $settings['twitter_card']) == 'summary_large_image' ? 'selected' : '' }}>summary_large_image (Recommended)</option>
                                            <option value="summary" {{ old('twitter_card', $settings['twitter_card']) == 'summary' ? 'selected' : '' }}>summary</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="twitter_site" class="form-label fw-bold">Twitter Site Handle (@StationHandle)</label>
                                        <input type="text" class="form-control" id="twitter_site" name="twitter_site" value="{{ old('twitter_site', $settings['twitter_site']) }}" placeholder="e.g. @StationHandle">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Twitter Card Image</label>
                                        @if(!empty($settings['twitter_image']) && file_exists(public_path($settings['twitter_image'])))
                                            <div class="mb-2 p-2 bg-light rounded text-center border">
                                                <img src="{{ asset($settings['twitter_image']) }}" alt="Twitter Image" style="max-height: 80px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" id="twitter_image_file" name="twitter_image_file" accept="image/*">
                                        <div class="form-text"><strong>Recommended Resolution:</strong> <code>1200 &times; 630 px</code>.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="twitter_title" class="form-label fw-bold">Twitter Card Title</label>
                                        <input type="text" class="form-control" id="twitter_title" name="twitter_title" value="{{ old('twitter_title', $settings['twitter_title']) }}" placeholder="Fallback: Open Graph Title">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="twitter_description" class="form-label fw-bold">Twitter Card Description</label>
                                        <input type="text" class="form-control" id="twitter_description" name="twitter_description" value="{{ old('twitter_description', $settings['twitter_description']) }}" placeholder="Fallback: Open Graph Description">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 12: AFFILIATE LINKS -->
                    <div class="tab-pane fade" id="sec-affiliate" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-link me-2"></i>12. Affiliate & Partner Links (Merchandise Store)</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="alert alert-info py-2 px-3 small mb-3">
                                    <i class="fas fa-info-circle me-1"></i> <strong>How to remove the Merchandise Store link:</strong> Clear both <strong>Affiliate 1 Label</strong> and <strong>Affiliate 1 URL</strong> below, then click <strong>Save Settings</strong> at the bottom of the page. When these fields are empty, the link will not appear on your website.
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="affiliate_link_1_label" class="form-label fw-bold">Affiliate 1 Label (Merchandise Store)</label>
                                        <input type="text" class="form-control" id="affiliate_link_1_label" name="affiliate_link_1_label" value="{{ old('affiliate_link_1_label', $settings['affiliate_link_1_label']) }}" placeholder="Leave blank to hide link">
                                        <div class="form-text">Leave blank to remove from footer.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="affiliate_link_1_url" class="form-label fw-bold">Affiliate 1 URL (Merchandise Store)</label>
                                        <input type="url" class="form-control" id="affiliate_link_1_url" name="affiliate_link_1_url" value="{{ old('affiliate_link_1_url', $settings['affiliate_link_1_url']) }}" placeholder="https://yourmerchstore.com">
                                        <div class="form-text">Leave blank to remove from footer.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="affiliate_link_2_label" class="form-label fw-bold">Affiliate 2 Label</label>
                                        <input type="text" class="form-control" id="affiliate_link_2_label" name="affiliate_link_2_label" value="{{ old('affiliate_link_2_label', $settings['affiliate_link_2_label']) }}" placeholder="e.g. Concert Tickets Partner">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="affiliate_link_2_url" class="form-label fw-bold">Affiliate 2 URL</label>
                                        <input type="url" class="form-control" id="affiliate_link_2_url" name="affiliate_link_2_url" value="{{ old('affiliate_link_2_url', $settings['affiliate_link_2_url']) }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="affiliate_link_3_label" class="form-label fw-bold">Affiliate 3 Label</label>
                                        <input type="text" class="form-control" id="affiliate_link_3_label" name="affiliate_link_3_label" value="{{ old('affiliate_link_3_label', $settings['affiliate_link_3_label']) }}" placeholder="e.g. Sponsor Link">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="affiliate_link_3_url" class="form-label fw-bold">Affiliate 3 URL</label>
                                        <input type="url" class="form-control" id="affiliate_link_3_url" name="affiliate_link_3_url" value="{{ old('affiliate_link_3_url', $settings['affiliate_link_3_url']) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 13: FRONT PAGE -->
                    <div class="tab-pane fade" id="sec-frontpage" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header card-settings-header p-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-desktop me-2"></i>13. Front Page Text & Section Messaging</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="front_hero_heading" class="form-label fw-bold">Hero Welcome Heading</label>
                                        <input type="text" class="form-control" id="front_hero_heading" name="front_hero_heading" value="{{ old('front_hero_heading', $settings['front_hero_heading']) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="front_request_button_text" class="form-label fw-bold">Song Request Button Label</label>
                                        <input type="text" class="form-control" id="front_request_button_text" name="front_request_button_text" value="{{ old('front_request_button_text', $settings['front_request_button_text']) }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="front_hero_subheading" class="form-label fw-bold">Hero Subheading Message</label>
                                        <input type="text" class="form-control" id="front_hero_subheading" name="front_hero_subheading" value="{{ old('front_hero_subheading', $settings['front_hero_subheading']) }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="front_featured_title" class="form-label fw-bold">Featured News Section Title</label>
                                        <input type="text" class="form-control" id="front_featured_title" name="front_featured_title" value="{{ old('front_featured_title', $settings['front_featured_title']) }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="front_about_text" class="form-label fw-bold">Footer About Station Paragraph</label>
                                        <textarea class="form-control" id="front_about_text" name="front_about_text" rows="3">{{ old('front_about_text', $settings['front_about_text']) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>
@endsection
