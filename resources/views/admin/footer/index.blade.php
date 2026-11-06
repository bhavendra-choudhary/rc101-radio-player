@extends('admin.layout')

@section('content')
    @php
        $seoSettings = $footer->seo_settings ?? [
            'title' => '',
            'description' => '',
            'image' => null,
        ];
    @endphp
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Appearance</h3>
                                    <p class="text-muted">Manage your website footer content, links, and social media</p>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('admin.footer.update') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')

                                        <!-- Company Info Section -->
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <h5 class="mb-3">Company Information</h5>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="brand_name">Brand Name</label>
                                                    <input type="text" class="form-control" id="brand_name" name="brand_name" value="{{ old('brand_name', $footer->brand_name) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="copyright_text">Copyright Text</label>
                                                    <input type="text" class="form-control" id="copyright_text" name="copyright_text" value="{{ old('copyright_text', $footer->copyright_text) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="description">Description</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description', $footer->description) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="logo">Upload Landscape Logo</label>
                                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/png, image/jpeg, image/jpg">
                                                    <small class="form-text text-muted">
                                                        <strong>Recommended Resolution:</strong> 400 &times; 120 px (or 512 &times; 128 px, transparent PNG).<br>
                                                        Upload a logo in PNG format. It will be saved as Landscape_Logo.png in the public folder.
                                                        @if(file_exists(public_path('Landscape_Logo.png')))
                                                            <br><strong>Current logo:</strong> <img src="{{ asset('Landscape_Logo.png') }}" alt="Current Logo" style="max-height: 30px; margin-top: 5px;">
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="favicon">Upload Favicon</label>
                                                    <input
                                                        type="file"
                                                        class="form-control"
                                                        id="favicon"
                                                        name="favicon"
                                                        accept=".png,.ico"
                                                    >

                                                    <small class="form-text text-muted">
                                                        <strong>Recommended Resolution:</strong> 32 &times; 32 px or 64 &times; 64 px (.ico or .png).<br>
                                                        Upload a favicon in PNG or ICO format. It will be saved as <strong>favicon.ico</strong> or <strong>favicon.png</strong> in the public folder.

                                                        @if(file_exists(public_path('favicon.ico')))
                                                            <br>
                                                            <strong>Current favicon:</strong>
                                                            <img
                                                                src="{{ asset('favicon.ico') }}"
                                                                alt="Current Favicon"
                                                                style="max-height: 30px; margin-top: 5px;"
                                                            >
                                                        @elseif(file_exists(public_path('favicon.png')))
                                                            <br>
                                                            <strong>Current favicon:</strong>
                                                            <img
                                                                src="{{ asset('favicon.png') }}"
                                                                alt="Current Favicon"
                                                                style="max-height: 30px; margin-top: 5px;"
                                                            >
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>


                                        </div>

                                        <!-- Theme Settings Section -->
                                        <!-- Theme Settings Section -->
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <h5 class="mb-3">Theme Colors & Settings</h5>
                                                <p class="text-muted">Customize your website's color scheme and appearance.</p>
                                            </div>

                                            @php
                                                $themeSettings = $footer->theme_settings ?? [
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
                                            @endphp

                                            <!-- Main Colors -->
                                            <div class="col-12">
                                                <h6 class="border-bottom pb-2 mb-3">Main Brand Colors</h6>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="primary_blue">Primary Blue (Buttons/Live Radio Background)</label>
                                                    <input type="color" class="form-control" id="primary_blue" name="primary_blue" value="{{ old('primary_blue', $themeSettings['primary_blue']) }}" required>
                                                    <small class="form-text text-muted">Used for main buttons and bottom radio station section background</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="secondary_blue">Secondary Blue (Button Hover)</label>
                                                    <input type="color" class="form-control" id="secondary_blue" name="secondary_blue" value="{{ old('secondary_blue', $themeSettings['secondary_blue']) }}" required>
                                                    <small class="form-text text-muted">Used for button hover states and secondary elements</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="accent_blue">Accent Blue (Navbar Hover)</label>
                                                    <input type="color" class="form-control" id="accent_blue" name="accent_blue" value="{{ old('accent_blue', $themeSettings['accent_blue']) }}" required>
                                                    <small class="form-text text-muted">Used for navbar hover effects and accent elements</small>
                                                </div>
                                            </div>

                                            <!-- Accent Colors -->
                                            <div class="col-12 mt-3">
                                                <h6 class="border-bottom pb-2 mb-3">Accent Colors</h6>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="accent_orange">Accent Orange</label>
                                                    <input type="color" class="form-control" id="accent_orange" name="accent_orange" value="{{ old('accent_orange', $themeSettings['accent_orange']) }}" required>
                                                    <small class="form-text text-muted">Used for special highlights and attention elements</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="accent_teal">Accent Teal</label>
                                                    <input type="color" class="form-control" id="accent_teal" name="accent_teal" value="{{ old('accent_teal', $themeSettings['accent_teal']) }}" required>
                                                    <small class="form-text text-muted">Used for secondary highlights and special elements</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="success_green">Success Green</label>
                                                    <input type="color" class="form-control" id="success_green" name="success_green" value="{{ old('success_green', $themeSettings['success_green']) }}" required>
                                                    <small class="form-text text-muted">Used for success messages and positive indicators</small>
                                                </div>
                                            </div>

                                            <!-- Background Colors -->
                                            <div class="col-12 mt-3">
                                                <h6 class="border-bottom pb-2 mb-3">Background Colors</h6>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="dark_gray">Footer background color</label>
                                                    <input type="color" class="form-control" id="dark_gray" name="dark_gray" value="{{ old('dark_gray', $themeSettings['dark_gray']) }}" required>
                                                    <small class="form-text text-muted">Footer Background color</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="warm_white">Warm White (Headings/Button Text/icon)</label>
                                                    <input type="color" class="form-control" id="warm_white" name="warm_white" value="{{ old('warm_white', $themeSettings['warm_white']) }}" required>
                                                    <small class="form-text text-muted">Used for heading text and button text and icon colors</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="light_gray">Light Gray Background</label>
                                                    <input type="color" class="form-control" id="light_gray" name="light_gray" value="{{ old('light_gray', $themeSettings['light_gray']) }}" required>
                                                    <small class="form-text text-muted">Used for light background sections</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="soft_yellow">Soft Yellow Background</label>
                                                    <input type="color" class="form-control" id="soft_yellow" name="soft_yellow" value="{{ old('soft_yellow', $themeSettings['soft_yellow']) }}" required>
                                                    <small class="form-text text-muted">Used for subtle yellow background areas</small>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="light_yellow">Light Yellow Background</label>
                                                    <input type="color" class="form-control" id="light_yellow" name="light_yellow" value="{{ old('light_yellow', $themeSettings['light_yellow']) }}" required>
                                                    <small class="form-text text-muted">Used for lighter yellow background areas</small>
                                                </div>
                                            </div>

                                            <!-- Text & Border Colors -->
                                            <div class="col-12 mt-3">
                                                <h6 class="border-bottom pb-2 mb-3">Text & Border Colors</h6>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="medium_gray">Medium Gray Text</label>
                                                    <input type="color" class="form-control" id="medium_gray" name="medium_gray" value="{{ old('medium_gray', $themeSettings['medium_gray']) }}" required>
                                                    <small class="form-text text-muted">Used for secondary text and descriptions</small>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="text_color">Primary Text Color</label>
                                                    <input type="color" class="form-control" id="text_color" name="text_color" value="{{ old('text_color', $themeSettings['text_color']) }}" required>
                                                    <small class="form-text text-muted">Used for main body text content</small>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="border_color">Border Color</label>
                                                    <input type="color" class="form-control" id="border_color" name="border_color" value="{{ old('border_color', $themeSettings['border_color']) }}" required>
                                                    <small class="form-text text-muted">Used for borders and separators</small>
                                                </div>
                                            </div>

                                            <!-- Player Settings -->
                                            <div class="col-12 mt-3">
                                                <h6 class="border-bottom pb-2 mb-3">Player Settings</h6>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="player_height">Player Height</label>
                                                    <input type="text" class="form-control" id="player_height" name="player_height" value="{{ old('player_height', $themeSettings['player_height']) }}" required placeholder="e.g., 80px">
                                                    <small class="form-text text-muted">Set the height of the audio player (e.g., 80px, 90px)</small>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- ================= SEO SETTINGS ================= -->
                                        <div class="row mt-5 mb-4">
                                            <div class="col-12">
                                                <h5 class="mb-3">SEO Settings ({{ $footer->brand_name ?? config('app.name') }})</h5>
                                                <p class="text-muted">
                                                    Configure SEO metadata for {{ $footer->brand_name ?? config('app.name') }}. Recommended limits are shown below.
                                                </p>
                                            </div>

                                            <!-- SEO Title -->
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="seo_title">SEO Title</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="seo_title"
                                                        name="seo_title"
                                                        value="{{ old('seo_title', $seoSettings['title']) }}"
                                                        maxlength="255"
                                                    >
                                                    <small id="seo_title_count" class="form-text text-muted">
                                                        0 / 60 characters (recommended)
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- SEO Description -->
                                            <div class="col-12 mt-3">
                                                <div class="form-group">
                                                    <label for="seo_description">SEO Description</label>
                                                    <textarea
                                                        class="form-control"
                                                        id="seo_description"
                                                        name="seo_description"
                                                        rows="4"
                                                        maxlength="500"
                                                    >{{ old('seo_description', $seoSettings['description']) }}</textarea>
                                                    <small id="seo_description_count" class="form-text text-muted">
                                                        0 / 160 characters (recommended)
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- SEO Image -->
                                            <div class="col-12 mt-3">
                                                <div class="form-group">
                                                    <label for="seo_image">SEO Image (Social Sharing)</label>
                                                    <input
                                                        type="file"
                                                        class="form-control"
                                                        id="seo_image"
                                                        name="seo_image"
                                                        accept="image/*"
                                                    >

                                                    @if(!empty($seoSettings['image']))
                                                        <div class="mt-2">
                                                            <p class="mb-1 text-muted">Current SEO Image:</p>
                                                            <img src="{{ str_starts_with($seoSettings['image'], 'http') ? $seoSettings['image'] : asset(ltrim($seoSettings['image'], '/')) }}" alt="SEO Image" style="max-height: 120px;">
                                                        </div>
                                                    @endif

                                                    <small class="form-text text-muted">
                                                        <strong>Recommended Resolution:</strong> 1200 &times; 630 px (used for Google, Facebook, WhatsApp previews).
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- ================= END SEO SETTINGS ================= -->

                                        <!-- Quick Links Section -->
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <h5 class="mb-3">Quick Links</h5>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="home_link_text">Home Link Text</label>
                                                    <input type="text" class="form-control" id="home_link_text" name="home_link_text" value="{{ old('home_link_text', $footer->home_link_text) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="home_link_url">Home Link URL</label>
                                                    <input type="text" class="form-control" id="home_link_url" name="home_link_url" value="{{ old('home_link_url', $footer->home_link_url) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="news_link_text">News Link Text</label>
                                                    <input type="text" class="form-control" id="news_link_text" name="news_link_text" value="{{ old('news_link_text', $footer->news_link_text) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="news_link_url">News Link URL</label>
                                                    <input type="text" class="form-control" id="news_link_url" name="news_link_url" value="{{ old('news_link_url', $footer->news_link_url) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="concerts_link_text">Concerts Link Text</label>
                                                    <input type="text" class="form-control" id="concerts_link_text" name="concerts_link_text" value="{{ old('concerts_link_text', $footer->concerts_link_text) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="concerts_link_url">Concerts Link URL</label>
                                                    <input type="text" class="form-control" id="concerts_link_url" name="concerts_link_url" value="{{ old('concerts_link_url', $footer->concerts_link_url) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="events_link_text">Events Link Text</label>
                                                    <input type="text" class="form-control" id="events_link_text" name="events_link_text" value="{{ old('events_link_text', $footer->events_link_text) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="events_link_url">Events Link URL</label>
                                                    <input type="text" class="form-control" id="events_link_url" name="events_link_url" value="{{ old('events_link_url', $footer->events_link_url) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_link_text">Contact Link Text</label>
                                                    <input type="text" class="form-control" id="contact_link_text" name="contact_link_text" value="{{ old('contact_link_text', $footer->contact_link_text) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_link_url">Contact Link URL</label>
                                                    <input type="text" class="form-control" id="contact_link_url" name="contact_link_url" value="{{ old('contact_link_url', $footer->contact_link_url) }}" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contact Info Section -->
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <h5 class="mb-3">Contact Information</h5>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="address">Address</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="address"
                                                        name="address"
                                                        value="{{ old('address', $footer->address === '#' ? '' : $footer->address) }}"
                                                    >
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="frequency">Frequency</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="frequency"
                                                        name="frequency"
                                                        value="{{ old('frequency', $footer->frequency === '#' ? '' : $footer->frequency) }}"
                                                    >
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group" style="display:none">
                                                    <label for="phone">Phone</label>
                                                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $footer->phone) }}" >
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group" style="display:none">
                                                    <label for="email">Email</label>
                                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $footer->email) }}" >
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Social Media Links Section -->
                                       <div class="row mb-4">
                                            <div class="col-12">
                                                <h5 class="mb-3">Social Media Links</h5>
                                                <p class="text-muted">Enter the full URLs for your social media profiles. Leave empty to hide the icon.</p>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="facebook_url">Facebook URL</label>
                                                    <input
                                                        type="url"
                                                        class="form-control"
                                                        id="facebook_url"
                                                        name="facebook_url"
                                                        value="{{ old('facebook_url', $footer->facebook_url === '#' ? '' : $footer->facebook_url) }}"
                                                        placeholder="https://facebook.com/yourpage"
                                                    >
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="instagram_url">Instagram URL</label>
                                                    <input
                                                        type="url"
                                                        class="form-control"
                                                        id="instagram_url"
                                                        name="instagram_url"
                                                        value="{{ old('instagram_url', $footer->instagram_url === '#' ? '' : $footer->instagram_url) }}"
                                                        placeholder="https://instagram.com/yourprofile"
                                                    >
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="twitter_url">Twitter URL</label>
                                                    <input
                                                        type="url"
                                                        class="form-control"
                                                        id="twitter_url"
                                                        name="twitter_url"
                                                        value="{{ old('twitter_url', $footer->twitter_url === '#' ? '' : $footer->twitter_url) }}"
                                                        placeholder="https://twitter.com/yourprofile"
                                                    >
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="youtube_url">YouTube URL</label>
                                                    <input
                                                        type="url"
                                                        class="form-control"
                                                        id="youtube_url"
                                                        name="youtube_url"
                                                        value="{{ old('youtube_url', $footer->youtube_url === '#' ? '' : $footer->youtube_url) }}"
                                                        placeholder="https://youtube.com/yourchannel"
                                                    >
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> Update Footer & Theme
                                                </button>
                                                <a href="{{ route('admin.index') }}" class="btn btn-secondary">
                                                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
        document.addEventListener('DOMContentLoaded', function () {

            const titleInput = document.getElementById('seo_title');
            const descInput = document.getElementById('seo_description');

            const titleCount = document.getElementById('seo_title_count');
            const descCount = document.getElementById('seo_description_count');

            const TITLE_LIMIT = 60;
            const DESC_LIMIT = 160;

            function updateCounter(input, counter, limit) {
                const length = input.value.length;
                counter.textContent = `${length} / ${limit} characters (recommended)`;

                if (length > limit) {
                    counter.classList.remove('text-muted');
                    counter.classList.add('text-danger');
                } else {
                    counter.classList.remove('text-danger');
                    counter.classList.add('text-muted');
                }
            }

            // Initial count (for edit page)
            updateCounter(titleInput, titleCount, TITLE_LIMIT);
            updateCounter(descInput, descCount, DESC_LIMIT);

            // Live typing
            titleInput.addEventListener('input', () => {
                updateCounter(titleInput, titleCount, TITLE_LIMIT);
            });

            descInput.addEventListener('input', () => {
                updateCounter(descInput, descCount, DESC_LIMIT);
            });

        });
    </script>

@endsection