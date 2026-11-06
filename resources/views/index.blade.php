@extends('layouts.app')

@section('title', \App\Models\SiteSetting::get('website_title', ($footer->brand_name ?? config('app.name')) . (\App\Models\SiteSetting::get('station_slogan') ? ' - ' . \App\Models\SiteSetting::get('station_slogan') : '')))



@section('content')
                                    <!-- Top Anchor for navigation -->
                                    <div id="top"></div>

                                    <!-- Enhanced Ads Carousel Section -->
                                    <section class="ads-section py-4">
                                        <div class="container">
                                            <div class="row justify-content-center">
                                                <div class="col-12 col-lg-10">
                                                    <div id="adsCarousel" class="carousel slide position-relative rounded-3 overflow-hidden" data-bs-ride="carousel"
                                                        data-bs-interval="5000">

                                                        <!-- Carousel items -->
                                                        <div class="carousel-inner rounded-3 overflow-hidden w-100">
                                                            @if($activeAds->isNotEmpty())
                                                                @foreach($activeAds as $index => $ad)
                                                                    @php
                                                                        $hasTargetUrl = !empty($ad->target_url) && $ad->target_url !== '#' && $ad->target_url !== 'javascript:void(0)';
                                                                        $bannerFallback = \App\Models\SiteSetting::get('banner_image') ? asset(\App\Models\SiteSetting::get('banner_image')) : asset('hero.jpg');
                                                                    @endphp
                                                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}" data-bs-interval="5000">
                                                                        <div class="w-100 h-100 position-relative">
                                                                            @if($ad->image_url)
                                                                                @if($hasTargetUrl)
                                                                                    <a href="{{ $ad->target_url }}" target="_blank" rel="noopener noreferrer" class="d-block w-100 h-100">
                                                                                        <img src="{{ $ad->image_url }}" class="w-100 h-100"
                                                                                            style="object-fit: cover; object-position: top center;"
                                                                                            alt="{{ $ad->title ?? 'Banner Ad' }}"
                                                                                            onerror="this.onerror=null; this.src='{{ $bannerFallback }}';">
                                                                                    </a>
                                                                                @else
                                                                                    <div class="w-100 h-100" style="cursor: default;" onclick="event.preventDefault(); return false;">
                                                                                        <img src="{{ $ad->image_url }}" class="w-100 h-100"
                                                                                            style="object-fit: cover; object-position: top center; cursor: default;"
                                                                                            alt="{{ $ad->title ?? 'Banner Ad' }}"
                                                                                            onerror="this.onerror=null; this.src='{{ $bannerFallback }}';">
                                                                                    </div>
                                                                                @endif
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <!-- Fallback content if no ads are available -->
                                                                <div class="carousel-item active">
                                                                    <div class="text-center p-4 bg-light rounded d-flex flex-column justify-content-center align-items-center h-100">
                                                                        <i class="fas fa-ad fa-2x text-muted mb-2"></i>
                                                                        <h5 class="text-muted mb-1">{{ config('app.name') }}</h5>
                                                                        <p class="text-muted small mb-0">Advertisement Space</p>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>

                                                    </div>

                                                    <!-- External Navigation Controls (Subtle, separated arrows, centered dots, non-intrusive) -->
                                                    @if($activeAds->count() > 1)
                                                        <div class="ads-carousel-nav d-flex align-items-center justify-content-between mt-2">
                                                            <button class="ads-nav-btn prev-btn" type="button" data-bs-target="#adsCarousel" data-bs-slide="prev" aria-label="Previous Ad" title="Previous Ad">
                                                                <i class="fas fa-chevron-left"></i>
                                                            </button>

                                                            <div class="ads-indicators-wrapper d-flex align-items-center justify-content-center">
                                                                @foreach($activeAds as $index => $ad)
                                                                    <button type="button" data-bs-target="#adsCarousel" data-bs-slide-to="{{ $index }}"
                                                                        class="ads-dot-indicator {{ $index === 0 ? 'active' : '' }}"
                                                                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                                                        aria-label="Ad Slide {{ $index + 1 }}"></button>
                                                                @endforeach
                                                            </div>

                                                            <button class="ads-nav-btn next-btn" type="button" data-bs-target="#adsCarousel" data-bs-slide="next" aria-label="Next Ad" title="Next Ad">
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    {{-- Ads carousel styles in public/css/custom.css --}}




                                    <script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        const carouselEl = document.getElementById('adsCarousel');
                                        if (carouselEl) {
                                            // Synchronize external dots with bootstrap carousel slides
                                            carouselEl.addEventListener('slide.bs.carousel', function (e) {
                                                const dots = document.querySelectorAll('.ads-dot-indicator');
                                                dots.forEach((dot, idx) => {
                                                    if (idx === e.to) {
                                                        dot.classList.add('active');
                                                        dot.setAttribute('aria-current', 'true');
                                                    } else {
                                                        dot.classList.remove('active');
                                                        dot.setAttribute('aria-current', 'false');
                                                    }
                                                });
                                            });

                                            let startX = 0;
                                            let startY = 0;

                                            carouselEl.addEventListener('touchstart', function (e) {
                                                if (e.touches.length === 1) {
                                                    startX = e.touches[0].clientX;
                                                    startY = e.touches[0].clientY;
                                                }
                                            }, { passive: true });

                                            carouselEl.addEventListener('touchend', function (e) {
                                                if (e.changedTouches.length === 1) {
                                                    const diffX = e.changedTouches[0].clientX - startX;
                                                    const diffY = e.changedTouches[0].clientY - startY;

                                                    if (Math.abs(diffX) > 35 && Math.abs(diffX) > Math.abs(diffY)) {
                                                        if (typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
                                                            const bsCarousel = bootstrap.Carousel.getInstance(carouselEl) || new bootstrap.Carousel(carouselEl);
                                                            if (diffX < 0) {
                                                                bsCarousel.next();
                                                            } else {
                                                                bsCarousel.prev();
                                                            }
                                                        }
                                                    }
                                                }
                                            }, { passive: true });
                                        }
                                    });
                                    </script>

                                    @php
                                        $fmtCfg = \App\Models\SiteSetting::getFormatConfig();
                                        $stationFormat = $fmtCfg['key'];
                                    @endphp

                                    <!-- Pop Culture News Section -->
                                    <section class="section" id="pop-culture-news">
                                        <x-pop-culture-news :news="$uploadedNews" />
                                    </section>
                                    <!-- Concerts Section -->
                                    <section class="section section-bg" id="concerts">
                                        <div class="container">
                                            <!-- Section Header: Concerts Nearby -->
                                            <div class="section-header text-center mb-4">
                                                <h3 class="display-5 concerts-nearby-title">
                                                    <i class="{{ $fmtCfg['concert_icon'] }} me-2 text-warning"></i>
                                                    {{ $fmtCfg['label'] }} Concerts Nearby
                                                </h3>
                                                <p id="concerts-lower-info" class="lead text-muted mt-2">
                                                    Find live {{ strtolower($fmtCfg['label']) }} concerts and events happening near you.
                                                </p>
                                            </div>

                                            <!-- Concerts Results Container -->
                                            <div id="homepage-concerts-container">
                                                <!-- Loading placeholder -->
                                                <div class="col-12 text-center" id="homepage-concerts-loading">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading concerts...</span>
                                                    </div>
                                                    <p class="mt-2 text-muted">Loading concerts...</p>
                                                </div>

                                                <!-- Concerts will be loaded here -->
                                                <div id="homepage-concerts-grid" class="row g-3 g-md-4" style="display: none;"></div>

                                                <!-- Load More Concerts Button Container (Solid Maroon Pill with Flanking Lines) -->
                                                <div id="concerts-load-more-container" class="concerts-load-more-wrapper my-4" style="display: none;">
                                                    <span class="load-more-divider-line"></span>
                                                    <button id="load-more-concerts-btn" class="concert-load-more-btn" onclick="loadMoreConcerts()">
                                                        <i class="fas fa-ticket-alt me-2 text-white"></i> Load More Concerts
                                                    </button>
                                                    <span class="load-more-divider-line"></span>
                                                </div>

                                                <!-- No concerts message -->
                                                <div class="col-12 text-center" id="homepage-no-concerts" style="display: none;">
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-music fa-2x mb-3 text-warning"></i>
                                                        <h4>No Concerts Found</h4>
                                                        <p class="mb-0">No concerts found in this area at the moment. Try searching another city below!</p>
                                                    </div>
                                                </div>

                                                <!-- Error message -->
                                                <div class="col-12 text-center" id="homepage-concerts-error" style="display: none;">
                                                    <div class="alert alert-danger">
                                                        <i class="fas fa-exclamation-triangle fa-2x mb-3 text-danger"></i>
                                                        <h4>Unable to Load Concerts</h4>
                                                        <p class="mb-0" id="homepage-error-message">Please try again later.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Concert Discovery & Search Section (positioned at bottom of concert results) -->
                                            <div class="concert-discovery-search-section">
                                                <div class="discovery-header-wrap">
                                                    <div class="discovery-header-icon">
                                                        <i class="fas fa-search"></i>
                                                    </div>
                                                    <div class="discovery-header-text">
                                                        <h4 class="concert-discovery-title">
                                                            Discover Top {{ $fmtCfg['label'] }} Music Concerts Happening Near You
                                                        </h4>
                                                        <p class="concert-discovery-subtext">
                                                            Search by city or artist to find upcoming {{ strtolower($fmtCfg['label']) }} concerts and events.
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Location Search Controls -->
                                                <div class="row justify-content-center">
                                                    <div class="col-lg-9 col-xl-8">
                                                        <div class="position-relative">
                                                            <div class="concert-search-pill shadow-sm">
                                                                <span class="search-pin-icon">
                                                                    <i class="fas fa-map-marker-alt"></i>
                                                                </span>

                                                                <input type="text"
                                                                       class="form-control"
                                                                       id="location-search-input"
                                                                       placeholder="Type City Name"
                                                                       autocomplete="off">

                                                                <button class="btn btn-search-location"
                                                                        type="button"
                                                                        id="use-my-location-btn"
                                                                        title="Use my current location">
                                                                    <i class="fas fa-crosshairs"></i>
                                                                </button>

                                                                <button class="btn btn-search-submit"
                                                                        type="button"
                                                                        id="location-search-btn">
                                                                    <i class="fas fa-search me-2"></i>Search
                                                                </button>
                                                            </div>

                                                            <!-- CITY DROPDOWN -->
                                                            <div id="city-suggestions"
                                                                class="city-suggestions-dropdown list-group shadow w-100">
                                                            </div>
                                                        </div>

                                                        <div class="concert-search-hint">
                                                            Find nearby concerts, tour dates and {{ strtolower($fmtCfg['label']) }} music events.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

{{--

    <!-- Trending Artists Subsection -->
                                            <div class="mt-5 pt-4 border-top border-light">
                                                <div class="section-header mb-4">
                                                    <h3 class="brand-font text-primary">🔥 Trending Artists</h3>
                                                    <p class="lead">Catch the biggest names in music on tour</p>
                                                </div>
                                                <div id="trending-artists-container" class="mt-4">
                                                <!-- Trending Artists Loading -->
                                                    <div class="col-12 text-center" id="trending-artists-loading">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Loading trending artists...</span>
                                                        </div>
                                                        <p class="mt-2">Loading trending artists...</p>
                                                    </div>
                                                </div>

                                                <!-- Trending Artists Grid -->
                                                <div class="row g-4" id="trending-artists-grid" style="display: none;"></div>

                                                <!-- No trending artists message -->
                                                <div class="col-12 text-center" id="trending-no-artists" style="display: none;">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-music fa-2x mb-3 text-info"></i>
                                                        <h4>No Trending Artists Found</h4>
                                                        <p class="mb-0">No trending artists currently have scheduled concerts. Check back later for
                                                            updates!</p>
                                                    </div>
                                                </div>

                                                <!-- Trending artists error message -->
                                                <div class="col-12 text-center" id="trending-artists-error" style="display: none;">
                                                    <div class="alert alert-danger">
                                                        <i class="fas fa-exclamation-triangle fa-2x mb-3 text-danger"></i>
                                                        <h4>Unable to Load Trending Artists</h4>
                                                        <p class="mb-0">We're having trouble loading trending artists. Please try again later.</p>
                                                    </div>
                                                </div>
                                            </div>
    --}}
                                            
                                        </div>
                                    </section>

                                    <!-- Events Section -->
                                    <section class="section" id="events">
                                        <div class="container">
                                            <div class="section-header text-center mb-4">
                                                <h3 class="display-5 community-events-header-title">
                                                    <i class="fas fa-users me-2 text-warning"></i>
                                                    Community Events
                                                </h3>
                                                <p class="lead text-muted mt-2">
                                                    Join us for these exciting events happening in your community.
                                                </p>
                                            </div>

                                            <!-- Events Container -->
                                            <div id="community-events-container">
                                                <!-- Loading placeholder -->
                                                <div class="col-12 text-center" id="community-events-loading">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading events...</span>
                                                    </div>
                                                    <p class="mt-2 text-muted">Loading events...</p>
                                                </div>

                                                <!-- Events will be loaded here -->
                                                <div id="community-events-grid" class="row g-4 d-none"></div>

                                                <!-- Error message -->
                                                <div class="col-12 text-center d-none" id="community-events-error">
                                                    <div class="alert alert-danger">
                                                        <i class="fas fa-exclamation-triangle fa-2x mb-3 text-danger"></i>
                                                        <h4>Unable to Load Events</h4>
                                                        <p class="mb-0">Please try again later.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Community Event Details Modal -->
                                    <div class="modal fade" id="communityEventModal" tabindex="-1" aria-labelledby="communityEventModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                <div class="modal-header text-white" style="background: linear-gradient(135deg, var(--primary-blue, #800400) 0%, var(--secondary-blue, #5b0a0c) 100%);">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fas fa-calendar-alt fa-lg text-warning"></i>
                                                        <h5 class="modal-title fw-bold mb-0 text-white" id="communityEventModalLabel">Event Details</h5>
                                                    </div>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4" id="communityEventModalBody">
                                                    <!-- Injected via JS -->
                                                </div>
                                                <div class="modal-footer bg-light border-0">
                                                    <a href="#" id="communityEventModalLink" class="btn btn-primary d-none" target="_blank" rel="noopener noreferrer">
                                                        <i class="fas fa-external-link-alt me-1"></i> Visit Event Website
                                                    </a>
                                                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Featured Contests Section -->
                                    <x-contest-modal />

                                    <!-- Contact Section -->
                                    <section class="section section-bg" id="contact">
                                        <div class="container">
                                            <div class="section-header text-center mb-4">
                                                <h3 class="display-5">
                                                    <i class="fas fa-envelope me-2 text-warning"></i>
                                                    Contact Us
                                                </h3>
                                                <p class="lead text-muted mt-2">
                                                    We'd love to hear from you! Send us a message and we'll get back to you soon.
                                                </p>
                                            </div>

                                            <div class="row justify-content-center">
                                                <div class="col-lg-10 col-xl-9">
                                                    <div class="contact-card shadow-sm">
                                                        <form id="contact-form" class="contact-form" method="POST" action="{{ route('contact.send') }}" hx-boost="false">
                                                            @csrf

                                                            <!-- Message box -->
                                                            <div id="contact-form-message" class="alert d-none"></div>

                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label for="name" class="form-label">Your Name</label>
                                                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" required>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <label for="subject" class="form-label">Subject</label>
                                                                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter a subject" required>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label for="email" class="form-label">Email Address</label>
                                                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <label for="message" class="form-label">Message</label>
                                                                    <textarea class="form-control" id="message" name="message" rows="1"
                                                                        placeholder="Enter your message" minlength="10" required></textarea>
                                                                </div>
                                                            </div>

                                                            <button type="submit" class="btn btn-contact-submit mt-2">
                                                                <i class="fas fa-paper-plane me-2"></i> Send Message
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <!-- Thank You Message (Hidden by default) -->
                                                    <div id="contact-success" class="alert alert-success text-center d-none mt-3">
                                                        <h4 class="mb-2">Thank you! 🎉</h4>
                                                        <p>Your message has been sent successfully. We’ll get back to you soon.</p>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </section>


                        <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const successBox = document.getElementById('contact-success');
                            const form = document.getElementById('contact-form');
                            const messageBox = document.getElementById('contact-form-message');
                            const submitBtn = form.querySelector('button[type="submit"]');

                            if (!form || !submitBtn) return;

                            const originalBtnHtml = submitBtn.innerHTML;

                            form.addEventListener('submit', function (e) {
                                e.preventDefault();

                                // Reset message
                                messageBox.className = 'alert d-none';
                                messageBox.innerHTML = '';

                                // Disable button + show loading
                                submitBtn.disabled = true;
                                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';

                                const formData = new FormData(form);

                                fetch(form.action, {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
                                    },
                                    body: formData
                                })
                                .then(async response => {
                                    const data = await response.json();
                                    if (!response.ok) throw data;
                                    return data;
                                })
                                .then(data => {
                                    // Reset form so user can send again if they want
                                    form.reset();

                                    // Show thank-you message
                                    successBox.classList.remove('d-none');

                                    // Hide the message after a few seconds
                                    setTimeout(() => {
                                        successBox.classList.add('d-none');
                                    }, 8000);
                                })


                                .catch(error => {
                                    if (error.errors) {
                                        showMessage(Object.values(error.errors).flat().join('<br>'), 'danger');
                                    } else {
                                        showMessage('Something went wrong. Please try again.', 'danger');
                                    }
                                })
                                .finally(() => {
                                    // Restore button
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalBtnHtml;
                                });
                            });

                            function showMessage(message, type) {
                                messageBox.className = `alert alert-${type}`;
                                messageBox.innerHTML = message;
                                messageBox.classList.remove('d-none');

                                setTimeout(() => {
                                    messageBox.classList.add('d-none');
                                }, 15000);
                            }
                        });
                        </script>




                        <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const searchBtn = document.getElementById('location-search-btn');

                            if (!searchBtn) return;

                            const iconHtml = '<i class="fas fa-search me-2"></i>';
                            const fullHtml = '<i class="fas fa-search me-2"></i>Search';

                            function updateSearchButton() {
                                if (window.innerWidth < 576) {
                                    searchBtn.innerHTML = iconHtml;
                                } else {
                                    searchBtn.innerHTML = fullHtml;
                                }
                            }

                            // Run on load
                            updateSearchButton();

                            // Run on resize
                            window.addEventListener('resize', updateSearchButton);
                        });

                        // Global variable for concerts
                        let allConcerts = [];
                        let visibleConcertsCount = 8;

                        // Load homepage country concerts based on GPS coordinates or fallback
                        function loadHomepageConcerts(coords = null) {
                            // Main concerts elements
                            const mainLoading = document.getElementById('homepage-concerts-loading');
                            const mainGrid = document.getElementById('homepage-concerts-grid');
                            const mainNoConcerts = document.getElementById('homepage-no-concerts');
                            const mainError = document.getElementById('homepage-concerts-error');
                            const loadMoreContainer = document.getElementById('concerts-load-more-container');

                            // Trending artists elements
                            const trendingLoading = document.getElementById('trending-artists-loading');
                            const trendingGrid = document.getElementById('trending-artists-grid');
                            const trendingNoArtists = document.getElementById('trending-no-artists');
                            const trendingError = document.getElementById('trending-artists-error');

                            // Show loading states
                            if (mainLoading) mainLoading.style.display = 'block';
                            if (mainGrid) mainGrid.style.display = 'none';
                            if (mainNoConcerts) mainNoConcerts.style.display = 'none';
                            if (mainError) mainError.style.display = 'none';
                            if (loadMoreContainer) loadMoreContainer.style.display = 'none';

                            if (trendingLoading) trendingLoading.style.display = 'none';
                            if (trendingGrid) trendingGrid.style.display = 'none';
                            if (trendingNoArtists) trendingNoArtists.style.display = 'none';
                            if (trendingError) trendingError.style.display = 'none';

                            let fetchUrl = '{{ url("api/concerts/homepage") }}';
                            if (coords && coords.latitude && coords.longitude) {
                                fetchUrl += `?latitude=${coords.latitude}&longitude=${coords.longitude}`;
                            }

                            fetch(fetchUrl)
                                .then(response => response.json())
                                .then(data => {
                                    // Hide loading states
                                    if (mainLoading) mainLoading.style.display = 'none';

                                    const homepageData = data;
                                    
                                    if (homepageData.success === true && Array.isArray(homepageData.concerts) && homepageData.concerts.length > 0) {
                                        allConcerts = homepageData.concerts;
                                        visibleConcertsCount = 8;
                                        
                                        // Clear grid and show it
                                        if (mainGrid) {
                                            mainGrid.innerHTML = '';
                                            mainGrid.style.display = 'flex';
                                        }

                                        // Hide empty state
                                        if (mainNoConcerts) mainNoConcerts.style.display = 'none';
                                        
                                        // Render all concerts
                                        renderConcertsBatch();
                                        
                                    } else {
                                        // API returned no concerts - show empty state
                                        if (mainGrid) mainGrid.style.display = 'none';
                                        if (mainNoConcerts) mainNoConcerts.style.display = 'block';
                                        if (loadMoreContainer) { loadMoreContainer.style.setProperty('display', 'none', 'important'); loadMoreContainer.classList.add('d-none'); }
                                    }
                                })
                                .catch(err => {
                                    console.error('Concerts loading error:', err);

                                    if (mainLoading) mainLoading.style.display = 'none';
                                    if (mainGrid) mainGrid.style.display = 'none';
                                    if (loadMoreContainer) { loadMoreContainer.style.setProperty('display', 'none', 'important'); loadMoreContainer.classList.add('d-none'); }
                                    if (mainError) mainError.style.display = 'block';
                                })
                                .finally(() => {
                                    if (typeof window.notifyAsyncLoadComplete === 'function') {
                                        window.notifyAsyncLoadComplete();
                                    }
                                });
                        }

                        function renderConcertsBatch() {
                            const grid = document.getElementById('homepage-concerts-grid');
                            
                            if (!grid) return;

                            if (allConcerts.length === 0) {
                                return;
                            }

                            let html = '';
                            const concertsToShow = allConcerts.slice(0, visibleConcertsCount);
                            
                            const defaultPlaceholder = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="250" viewBox="0 0 400 250"><defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="%231e293b"/><stop offset="100%" stop-color="%230f172a"/></linearGradient></defs><rect width="400" height="250" fill="url(%23g)"/><text x="50%" y="45%" dominant-baseline="middle" text-anchor="middle" font-size="44" fill="%23ffc107">🎵</text><text x="50%" y="70%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="16" font-weight="bold" fill="%23ffffff">{{ $fmtCfg['label'] }} Concert</text></svg>';

                            concertsToShow.forEach(concert => {
                                const concertImg = (concert.image && !concert.image.includes('placeholder')) ? concert.image : defaultPlaceholder;
                                const concertTitle = concert.name || '{{ $fmtCfg['label'] }} Concert';
                                const artistName = (concert.artist && concert.artist !== concert.name) ? concert.artist : '';

                                html += '<div class="col-12 col-sm-6 col-lg-3 animate__animated animate__fadeIn mb-3">';
                                html += '<div class="card h-100 concert-card shadow-sm border-0 rounded-3 overflow-hidden">';
                                html += '<div class="concert-img-wrap">';
                                html += '<img src="' + concertImg + '" class="card-img-top" alt="' + concertTitle + '" onerror="this.onerror=null; this.src=\'' + defaultPlaceholder + '\';">';
                                html += '</div>';

                                html += '<div class="card-body d-flex flex-column">';
                                html += '<h5 class="card-title text-truncate" title="' + concertTitle + '">' + concertTitle + '</h5>';
                                if (artistName) {
                                    html += '<p class="concert-artist-name text-truncate" title="' + artistName + '">' + artistName + '</p>';
                                }
                                html += '<div class="concert-meta-info">';
                                html += '<div><i class="fas fa-calendar text-danger me-1"></i> ' + (concert.date || 'Date TBD') + '</div>';
                                html += '<div class="text-truncate"><i class="fas fa-map-marker-alt text-danger me-1"></i> ' + (concert.venue?.name || 'Venue') + ', ' + (concert.venue?.city || 'City') + '</div>';
                                html += '</div>';
                                html += '<a href="' + (concert.url || '#') +
                                    '" target="_blank" rel="noopener noreferrer" class="btn btn-ticket mt-auto"><i class="fas fa-ticket-alt me-1"></i> View Tickets</a>';
                                html += '</div>';
                                html += '</div>';
                                html += '</div>';
                            });

                            // Add to grid
                            grid.innerHTML = html;

                            // Check load more button
                            let loadMoreContainer = document.getElementById('concerts-load-more-container');
                            if (!loadMoreContainer) {
                                loadMoreContainer = document.createElement('div');
                                loadMoreContainer.id = 'concerts-load-more-container';
                                loadMoreContainer.className = 'concerts-load-more-wrapper my-4';
                                loadMoreContainer.innerHTML = '<span class="load-more-divider-line"></span><button id="load-more-concerts-btn" class="concert-load-more-btn" onclick="loadMoreConcerts()"><i class="fas fa-ticket-alt me-2 text-white"></i> Load More Concerts</button><span class="load-more-divider-line"></span>';
                                grid.insertAdjacentElement('afterend', loadMoreContainer);
                            } else {
                                if (!document.getElementById('load-more-concerts-btn')) {
                                    loadMoreContainer.innerHTML = '<span class="load-more-divider-line"></span><button id="load-more-concerts-btn" class="concert-load-more-btn" onclick="loadMoreConcerts()"><i class="fas fa-ticket-alt me-2 text-white"></i> Load More Concerts</button><span class="load-more-divider-line"></span>';
                                }
                            }
                            
                            if (allConcerts.length > visibleConcertsCount) {
                                loadMoreContainer.style.setProperty('display', 'flex', 'important');
                                loadMoreContainer.classList.remove('d-none');
                            } else {
                                loadMoreContainer.style.setProperty('display', 'none', 'important');
                                loadMoreContainer.classList.add('d-none');
                            }
                        }

                        function loadMoreConcerts() {
                            visibleConcertsCount += 8;
                            renderConcertsBatch();
                        }

                        function displayConcerts(concerts) {
                            // Legacy function kept for compatibility if needed, but logic is moved to renderConcertsBatch
                            console.warn('displayConcerts is deprecated, use renderConcertsBatch instead');
                        }

                        function displayTrendingArtists(concertsByArtist) {
                            const grid = document.getElementById('trending-artists-grid');
                            const noArtists = document.getElementById('trending-no-artists');

                            if (!grid) return;

                            // Flatten all concerts from all artists
                            let allConcerts = [];
                            Object.values(concertsByArtist).forEach(artistConcerts => {
                                allConcerts = allConcerts.concat(artistConcerts);
                            });

                            // Sort concerts by date
                            allConcerts.sort((a, b) => {
                                const dateA = new Date(a.date + ' ' + (a.time || '00:00'));
                                const dateB = new Date(b.date + ' ' + (b.time || '00:00'));
                                return dateA - dateB;
                            });

                            // Limit to 12 concerts for compact display
                            const limitedConcerts = allConcerts.slice(0, 12);

                            let html = '';
                            limitedConcerts.forEach(concert => {
                                html += '<div class="col-12 col-md-6 col-lg-4">';
                                html += '<div class="card h-100 border-0 shadow-sm trending-concert-card">';

                                // Add trending badge
                                html += '<div class="position-absolute top-0 end-0 m-2 z-1">';
                                html += '<span class="badge bg-primary">Trending</span>';
                                html += '</div>';

                                html += '<img src="' + (concert.image && !concert.image.includes('placeholder') ? concert.image : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiB2aWV3Qm94PSIwIDAgMzAwIDIwMCI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiM3YjExMTMiLz48dGV4dCB4PSI1MCUiIHk9IjQ1JSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iNDgiIGZpbGw9IndoaXRlIj7wn47tPC90ZXh0Pjx0ZXh0IHg9IjUwJSIgeT0iNzAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjcpIj5Db25jZXJ0PC90ZXh0Pjwvc3ZnPg==') +
                                    '" class="card-img-top" alt="' + concert.name + '" style="height: 160px; object-fit: contain;">';

                                html += '<div class="card-body p-3 d-flex flex-column">';
                                html += '<h6 class="card-title mb-2 fw-bold">' + concert.name + '</h6>';
                                html += '<p class="card-text small text-primary mb-2"><strong>' + (concert.artist || concert.name) +
                                    '</strong></p>';
                                html += '<p class="card-text small text-muted mb-3">';
                                html += '<i class="fas fa-calendar me-1"></i> ' + (concert.date || 'Date TBD') + '<br>';
                                html += '<i class="fas fa-map-marker-alt me-1"></i> ' + (concert.venue?.name || 'Venue') + ', ' + (
                                    concert.venue?.city || 'City');
                                html += '</p>';
                                html += '<a href="' + concert.url +
                                    '" target="_blank" class="btn btn-sm btn-primary w-100 mt-auto">Get Tickets</a>';
                                html += '</div>';
                                html += '</div>';
                                html += '</div>';
                            });

                            grid.innerHTML = html;
                            grid.style.display = 'flex';

                            if (noArtists) noArtists.style.display = 'none';
                        }

                        // Centralized Radio Location Service
                        window.RadioLocation = {
                            STORAGE_KEY: 'radio_user_location',
                            PROMPTED_KEY: 'radio_location_prompted',

                            getResolvedLocation: function() {
                                try {
                                    const raw = localStorage.getItem(this.STORAGE_KEY);
                                    if (raw) {
                                        const parsed = JSON.parse(raw);
                                        if (parsed && typeof parsed.latitude === 'number' && typeof parsed.longitude === 'number') {
                                            return parsed;
                                        }
                                    }
                                } catch(e) {}
                                // Fallback to legacy cookie
                                const legacyCoords = getLocationCookie('user_location_coords');
                                if (legacyCoords) {
                                    const parts = legacyCoords.split(',');
                                    const lat = parseFloat(parts[0]);
                                    const lng = parseFloat(parts[1]);
                                    if (!isNaN(lat) && !isNaN(lng)) {
                                        const city = getLocationCookie('user_location_city') || '';
                                        return { latitude: lat, longitude: lng, cityName: city };
                                    }
                                }
                                return null;
                            },

                            saveResolvedLocation: function(latitude, longitude, cityName) {
                                const data = {
                                    latitude: parseFloat(latitude),
                                    longitude: parseFloat(longitude),
                                    cityName: cityName || '',
                                    savedAt: Date.now()
                                };
                                try {
                                    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(data));
                                    localStorage.setItem(this.PROMPTED_KEY, '1');
                                    sessionStorage.setItem(this.PROMPTED_KEY, '1');
                                } catch(e) {}
                                setLocationCookie('user_location_coords', `${latitude},${longitude}`, 365);
                                setLocationCookie('user_location_prompted', '1', 365);
                                if (cityName) {
                                    setLocationCookie('user_location_city', cityName, 365);
                                }
                            },

                            hasBeenPromptedOrDismissed: function() {
                                try {
                                    if (localStorage.getItem(this.PROMPTED_KEY) === '1' || sessionStorage.getItem(this.PROMPTED_KEY) === '1') {
                                        return true;
                                    }
                                } catch(e) {}
                                return getLocationCookie('user_location_prompted') === '1';
                            },

                            markPrompted: function() {
                                try {
                                    localStorage.setItem(this.PROMPTED_KEY, '1');
                                    sessionStorage.setItem(this.PROMPTED_KEY, '1');
                                } catch(e) {}
                                setLocationCookie('user_location_prompted', '1', 30);
                            }
                        };

                        // Cookie helper utilities for location persistence
                        function getLocationCookie(name) {
                            const value = `; ${document.cookie}`;
                            const parts = value.split(`; ${name}=`);
                            if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
                            return null;
                        }

                        function setLocationCookie(name, value, days) {
                            days = days || 365;
                            const d = new Date();
                            d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
                            document.cookie = `${name}=${encodeURIComponent(value)}; expires=${d.toUTCString()}; path=/; SameSite=Lax`;
                        }

                        document.addEventListener('DOMContentLoaded', function() {
                            const locationSearchInput = document.getElementById('location-search-input');
                            const locationSearchBtn = document.getElementById('location-search-btn');
                            const useMyLocationBtn = document.getElementById('use-my-location-btn');

                            initLocationControls();

                            // 1. Check if location has already been resolved and saved
                            const savedLocation = window.RadioLocation.getResolvedLocation();
                            if (savedLocation) {
                                if (savedLocation.cityName && locationSearchInput) {
                                    locationSearchInput.value = savedLocation.cityName;
                                }
                                loadHomepageConcerts({ latitude: savedLocation.latitude, longitude: savedLocation.longitude });
                                return;
                            }

                            // 2. Determine permission state via navigator.permissions if available
                            if (navigator.permissions && navigator.permissions.query) {
                                navigator.permissions.query({ name: 'geolocation' }).then(function(result) {
                                    if (result.state === 'granted') {
                                        // Permission already granted: retrieve silently using cached GPS (never prompts!)
                                        navigator.geolocation.getCurrentPosition(
                                            function(position) {
                                                const lat = position.coords.latitude;
                                                const lng = position.coords.longitude;
                                                window.RadioLocation.saveResolvedLocation(lat, lng, '');
                                                loadHomepageConcerts({ latitude: lat, longitude: lng });
                                                reverseGeocode(lat, lng).then(cityName => {
                                                    if (cityName) {
                                                        window.RadioLocation.saveResolvedLocation(lat, lng, cityName);
                                                        if (locationSearchInput) locationSearchInput.value = cityName;
                                                    }
                                                }).catch(() => {});
                                            },
                                            function() {
                                                loadHomepageConcerts();
                                            },
                                            { enableHighAccuracy: false, timeout: 6000, maximumAge: 600000 }
                                        );
                                    } else if (result.state === 'denied') {
                                        // Permission denied: do not spam or prompt, load default concerts
                                        window.RadioLocation.markPrompted();
                                        loadHomepageConcerts();
                                    } else {
                                        // Permission state is 'prompt'
                                        if (window.RadioLocation.hasBeenPromptedOrDismissed()) {
                                            loadHomepageConcerts();
                                        } else {
                                            // First visit: request once
                                            requestInitialLocation();
                                        }
                                    }
                                }).catch(function() {
                                    fallbackLocationCheck();
                                });
                            } else {
                                fallbackLocationCheck();
                            }

                            function fallbackLocationCheck() {
                                if (window.RadioLocation.hasBeenPromptedOrDismissed()) {
                                    loadHomepageConcerts();
                                } else {
                                    requestInitialLocation();
                                }
                            }

                            function requestInitialLocation() {
                                if (!navigator.geolocation) {
                                    loadHomepageConcerts();
                                    return;
                                }

                                window.RadioLocation.markPrompted();
                                navigator.geolocation.getCurrentPosition(
                                    function(position) {
                                        const latitude = position.coords.latitude;
                                        const longitude = position.coords.longitude;
                                        window.RadioLocation.saveResolvedLocation(latitude, longitude, '');
                                        loadHomepageConcerts({ latitude: latitude, longitude: longitude });

                                        reverseGeocode(latitude, longitude).then(cityName => {
                                            if (cityName) {
                                                window.RadioLocation.saveResolvedLocation(latitude, longitude, cityName);
                                                if (locationSearchInput) locationSearchInput.value = cityName;
                                            }
                                        }).catch(() => {});
                                    },
                                    function(err) {
                                        window.RadioLocation.markPrompted();
                                        loadHomepageConcerts();
                                    },
                                    { enableHighAccuracy: false, timeout: 6000, maximumAge: 300000 }
                                );
                            }

                            function initLocationControls() {
                                // Location search functionality
                                if (locationSearchInput && locationSearchBtn && !locationSearchBtn._boundSearch) {
                                    locationSearchBtn._boundSearch = true;
                                    locationSearchBtn.addEventListener('click', function() {
                                        performLocationSearch();
                                    });

                                    locationSearchInput.addEventListener('keypress', function(e) {
                                        if (e.key === 'Enter') {
                                            performLocationSearch();
                                        }
                                    });
                                }

                                // Use My Location button functionality (explicit user click)
                                if (useMyLocationBtn && !useMyLocationBtn._boundLocation) {
                                    useMyLocationBtn._boundLocation = true;
                                    useMyLocationBtn.addEventListener('click', function() {
                                        getUserLocation();
                                    });
                                }
                            }

                            function getUserLocation() {
                                if (!navigator.geolocation) {
                                    alert('Geolocation is not supported by this browser. Please enter a city name manually.');
                                    return;
                                }

                                // Show loading state on the button
                                const originalHtml = useMyLocationBtn.innerHTML;
                                useMyLocationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                                useMyLocationBtn.disabled = true;

                                navigator.geolocation.getCurrentPosition(
                                    // Success callback
                                    function(position) {
                                        const latitude = position.coords.latitude;
                                        const longitude = position.coords.longitude;

                                        window.RadioLocation.saveResolvedLocation(latitude, longitude, '');
                                        loadHomepageConcerts({ latitude: latitude, longitude: longitude });

                                        reverseGeocode(latitude, longitude)
                                            .then(cityName => {
                                                if (cityName) {
                                                    window.RadioLocation.saveResolvedLocation(latitude, longitude, cityName);
                                                    if (locationSearchInput) {
                                                        locationSearchInput.value = cityName;
                                                    }
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Reverse geocoding error:', error);
                                            })
                                            .finally(() => {
                                                useMyLocationBtn.innerHTML = originalHtml;
                                                useMyLocationBtn.disabled = false;
                                            });
                                    },
                                    // Error callback
                                    function(error) {
                                        console.error('Geolocation error:', error);
                                        useMyLocationBtn.innerHTML = originalHtml;
                                        useMyLocationBtn.disabled = false;
                                        alert('Unable to get your location. Please enter a city name manually.');
                                    },
                                    { enableHighAccuracy: false, timeout: 8000, maximumAge: 60000 }
                                );
                            }

                            function reverseGeocode(latitude, longitude) {
                                return new Promise((resolve, reject) => {
                                    const url =
                                        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=10`;

                                    fetch(url, {
                                            headers: {
                                                'User-Agent': 'Radio Station App/1.0'
                                            }
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data && data.address) {
                                                // Try to get city name from different possible fields
                                                const city = data.address.city ||
                                                    data.address.town ||
                                                    data.address.village ||
                                                    data.address.municipality ||
                                                    data.address.county ||
                                                    data.address.state;

                                                if (city) {
                                                    resolve(city);
                                                } else {
                                                    reject(new Error('No city found in reverse geocoding response'));
                                                }
                                            } else {
                                                reject(new Error('Invalid reverse geocoding response'));
                                            }
                                        })
                                        .catch(error => {
                                            reject(error);
                                        });
                                });
                            }

                            function getIPLocation() {
                                // Try IP-based location as fallback
                                fetch('/debug/location')
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.ip_location && data.ip_location.city) {
                                            locationSearchInput.value = data.ip_location.city;
                                            // Automatically trigger search
                                            performLocationSearch();
                                        }
                                    })
                                    .catch(error => {
                                        console.error('IP location fallback failed:', error);
                                    });
                            }

                            function performLocationSearch() {
                                const location = locationSearchInput.value.trim();

                                if (!location) {
                                    alert('Please enter a city name');
                                    return;
                                }

                                // Show loading state
                                const loading = document.getElementById('homepage-concerts-loading');
                                const grid = document.getElementById('homepage-concerts-grid');
                                const error = document.getElementById('homepage-concerts-error');
                                const noConcerts = document.getElementById('homepage-no-concerts');
                                const loadMoreContainer = document.getElementById('concerts-load-more-container');

                                if (loading) loading.style.display = 'block';
                                if (grid) grid.style.display = 'none';
                                if (error) error.style.display = 'none';
                                if (noConcerts) noConcerts.style.display = 'none';
                                if (loadMoreContainer) loadMoreContainer.style.display = 'none';

                                // Update loading text
                                if (loading) {
                                    const loadingText = loading.querySelector('p');
                                    if (loadingText) {
                                        loadingText.textContent = `Searching concerts in ${location}...`;
                                    }
                                }

                                // Fetch concerts for the location
                                fetch(`/api/concerts/location?location=${encodeURIComponent(location)}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (loading) loading.style.display = 'none';

                                        if (data.success && data.concerts && data.concerts.length > 0) {
                                            // Store all concerts and initialize pagination
                                            allConcerts = data.concerts;
                                            visibleConcertsCount = 8;

                                            // Clear grid and show it
                                            if (grid) {
                                                grid.innerHTML = '';
                                                grid.style.display = 'flex';
                                            }

                                            // Render first batch
                                            renderConcertsBatch();

                                            // Update section title to show location
                                            const sectionTitle = document.querySelector('#concerts .section-header h3');
                                            if (sectionTitle) {
                                                sectionTitle.innerHTML = `<i class="{{ $fmtCfg['concert_icon'] }} me-2 text-warning"></i> Concerts in ${data.location}`;
                                            }
                                        } else {
                                            // Show no concerts message with location context
                                            if (noConcerts) {
                                                const noConcertsTitle = noConcerts.querySelector('h4');
                                                const noConcertsText = noConcerts.querySelector('p');
                                                if (noConcertsTitle) {
                                                    noConcertsTitle.textContent = `No Concerts Found in ${location}`;
                                                }
                                                if (noConcertsText) {
                                                    noConcertsText.textContent =
                                                        `No concerts found in ${location}. Try a different city or check back later!`;
                                                }
                                                noConcerts.style.display = 'block';
                                            }
                                        }
                                    })
                                    .catch(err => {
                                        console.error('Location search error:', err);
                                        if (loading) loading.style.display = 'none';
                                        if (error) {
                                            const errorTitle = error.querySelector('h4');
                                            const errorText = error.querySelector('p');
                                            if (errorTitle) {
                                                errorTitle.textContent = 'Search Error';
                                            }
                                            if (errorText) {
                                                errorText.textContent =
                                                    `Unable to search concerts in ${location}. Please try again later.`;
                                            }
                                            error.style.display = 'block';
                                        }
                                    });
                            }

                            // Load homepage concerts when page is ready
                            document.addEventListener('DOMContentLoaded', function() {
                                // Load homepage concerts after a short delay
                                setTimeout(loadHomepageConcerts, 1000);
                            });
                        });
                        </script>
                    <script>
                    const usCities = [
                        "New York",
                        "Los Angeles",
                        "Chicago",
                        "Houston",
                        "Phoenix",
                        "Philadelphia",
                        "San Antonio",
                        "San Diego",
                        "San Jose",
                        "Dallas",
                        "Austin",
                        "Seattle",
                        "Denver",
                        "Boston",
                        "Miami",
                        "Orlando",
                        "Atlanta",
                        "Nashville",
                        "Las Vegas",
                        "San Francisco"
                    ];
                    </script>
<script>
// City suggestions functionality
const input = document.getElementById("location-search-input");
const suggestionsBox = document.getElementById("city-suggestions");

input.addEventListener("input", function () {
    const value = this.value.toLowerCase().trim();
    suggestionsBox.innerHTML = "";

    if (value.length < 2) {
        suggestionsBox.style.display = "none";
        return;
    }

    const filteredCities = usCities.filter(city =>
        city.toLowerCase().startsWith(value)
    );

    if (filteredCities.length === 0) {
        suggestionsBox.style.display = "none";
        return;
    }

    filteredCities.forEach(city => {
        const item = document.createElement("div");
        item.className = "list-group-item list-group-item-action";
        item.textContent = city;

        item.addEventListener("click", () => {
            input.value = city;
            suggestionsBox.style.display = "none";
            
            // Auto trigger search
            const searchBtn = document.getElementById('location-search-btn');
            if (searchBtn) searchBtn.click();
        });

        suggestionsBox.appendChild(item);
    });

    suggestionsBox.style.display = "block";
});

// Close suggestions when clicking outside
document.addEventListener("click", function(e) {
    if (!e.target.closest(".position-relative")) {
        suggestionsBox.style.display = "none";
    }
});

// Load Community Events
function loadCommunityEvents() {
    const loading = document.getElementById('community-events-loading');
    const grid = document.getElementById('community-events-grid');
    const noEvents = document.getElementById('community-no-events');
    const error = document.getElementById('community-events-error');

    // Show loading state
    if (loading) loading.style.display = 'block';
    if (grid) grid.style.display = 'none';
    if (noEvents) noEvents.style.display = 'none';
    if (error) error.style.display = 'none';

    // Fetch community events from API
    fetch('{{ url("api/community-events") }}')
        .then(response => {
            if (!response.ok) {
                throw new Error('Server error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // If the server returned a JSON error object, throw to trigger .catch()
            if (data && data.error) {
                throw new Error(data.error);
            }
            return data;
        })
        .then(events => {
            // Hide loading
            if (loading) loading.style.display = 'none';

            const defaultEvents = [
                {
                    title: 'Meet & Greet',
                    icon: 'users',
                    description: 'Meet your favorite country stars and win exclusive merchandise at our meet and greets.',
                    source_url: '{{ route("events.meet-and-greet") }}'
                },
                {
                    title: 'Studio Tour',
                    icon: 'broadcast-tower',
                    description: 'Get a behind-the-scenes look at our broadcasting facilities and see how radio magic happens.',
                    source_url: '{{ route("events.studio-tour") }}'
                },
                {
                    title: 'Charity Event',
                    icon: 'calendar-check',
                    description: 'Join us for our annual charity event supporting local causes and making a difference.',
                    source_url: '{{ route("events.charity-event") }}'
                }
            ];

            const eventsToDisplay = (Array.isArray(events) && events.length > 0) ? events : defaultEvents;
            window.loadedCommunityEvents = eventsToDisplay;

            function renderEventsHtml(eventList) {
                return eventList.map((event, index) => {
                    const iconName = event.icon || (event.title.includes('Meet') ? 'users' : (event.title.includes('Studio') ? 'broadcast-tower' : 'calendar-check'));
                    let eventUrl = event.source_url;
                    if (!eventUrl || eventUrl === '#') {
                        if (event.title.includes('Meet')) eventUrl = '{{ route("events.meet-and-greet") }}';
                        else if (event.title.includes('Studio')) eventUrl = '{{ route("events.studio-tour") }}';
                        else if (event.title.includes('Charity')) eventUrl = '{{ route("events.charity-event") }}';
                        else eventUrl = '';
                    }

                    const hasImg = !!event.image;
                    const bannerStyle = hasImg 
                        ? `background-image: linear-gradient(135deg, rgba(40, 10, 15, 0.72) 0%, rgba(20, 5, 8, 0.85) 100%), url('${event.image}') !important; background-size: cover !important; background-position: center !important;`
                        : '';
                    const bannerClass = hasImg ? 'event-header-banner has-bg-img' : 'event-header-banner';

                    let linkHtml = '';
                    if (eventUrl && eventUrl.startsWith('http') && !eventUrl.includes(window.location.host)) {
                        linkHtml = `
                            <a href="${eventUrl}" target="_blank" rel="noopener noreferrer" class="event-card-link" aria-label="Learn more about ${event.title}">
                                Learn More <i class="fas fa-external-link-alt ms-1"></i>
                            </a>
                        `;
                    } else if (eventUrl && (eventUrl.includes('/events/') || (eventUrl.startsWith('/') && eventUrl.length > 1))) {
                        linkHtml = `
                            <a href="${eventUrl}" class="event-card-link" aria-label="Learn more about ${event.title}">
                                Learn More <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        `;
                    } else {
                        linkHtml = `
                            <button type="button" class="btn btn-link p-0 text-decoration-none event-card-link" onclick="openCommunityEventModal(${index})" aria-label="Learn more about ${event.title}">
                                Learn More <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        `;
                    }

                    return `
                    <div class="col-12 col-md-4 mb-4">
                        <div class="card h-100 event-card shadow-sm border-0 rounded-3 overflow-hidden" style="cursor: pointer;" onclick="if(!event.target.closest('a') && !event.target.closest('button')) openCommunityEventModal(${index})">
                            <div class="${bannerClass}" ${bannerStyle ? `style="${bannerStyle}"` : ''}>
                                <div class="event-banner-icon-box">
                                    <i class="fas fa-${iconName}"></i>
                                </div>
                                <h4 class="event-banner-title mb-0">${event.title}</h4>
                            </div>
                            <div class="card-body p-4 d-flex flex-column">
                                <p class="event-card-desc flex-grow-1 mb-3">${event.description}</p>
                                <div class="mt-auto text-end">
                                    ${linkHtml}
                                </div>
                            </div>
                        </div>
                    </div>
                `}).join('');
            }

            if (grid) {
                grid.innerHTML = renderEventsHtml(eventsToDisplay);
                grid.classList.remove('d-none');
                grid.style.display = 'flex';
            }
            if (noEvents) noEvents.style.display = 'none';
        })
        .catch(err => {
            console.warn('Could not load events from API, displaying default events:', err);
            if (loading) loading.style.display = 'none';
            if (error) error.style.display = 'none';
            if (grid) {
                const defaultEventsFallback = [
                    {
                        title: 'Meet & Greet',
                        icon: 'users',
                        description: 'Meet your favorite country stars and win exclusive merchandise at our meet and greets.',
                        source_url: '{{ route("events.meet-and-greet") }}'
                    },
                    {
                        title: 'Studio Tour',
                        icon: 'broadcast-tower',
                        description: 'Get a behind-the-scenes look at our broadcasting facilities and see how radio magic happens.',
                        source_url: '{{ route("events.studio-tour") }}'
                    },
                    {
                        title: 'Charity Event',
                        icon: 'calendar-check',
                        description: 'Join us for our annual charity event supporting local causes and making a difference.',
                        source_url: '{{ route("events.charity-event") }}'
                    }
                ];
                window.loadedCommunityEvents = defaultEventsFallback;
                grid.innerHTML = renderEventsHtml(defaultEventsFallback);
                grid.classList.remove('d-none');
                grid.style.display = 'flex';
            }
        })
        .finally(() => {
            if (typeof window.notifyAsyncLoadComplete === 'function') {
                window.notifyAsyncLoadComplete();
            }
        });
}

window.openCommunityEventModal = function(index) {
    const event = (window.loadedCommunityEvents && window.loadedCommunityEvents[index]) ? window.loadedCommunityEvents[index] : null;
    if (!event) return;

    const modalTitle = document.getElementById('communityEventModalLabel');
    const modalBody = document.getElementById('communityEventModalBody');
    const modalLink = document.getElementById('communityEventModalLink');

    if (modalTitle) modalTitle.textContent = event.title;

    let dateBadge = '';
    const dateText = event.formatted_date || (event.event_date ? new Date(event.event_date).toLocaleString() : '');
    if (dateText) {
        dateBadge = `
            <div class="mb-2 d-flex align-items-center text-muted">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                <span class="fw-semibold">${dateText}</span>
            </div>
        `;
    }

    let locationBadge = '';
    if (event.location) {
        locationBadge = `
            <div class="mb-3 d-flex align-items-center text-muted">
                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                <span>${event.location}</span>
            </div>
        `;
    }

    let imageHtml = '';
    if (event.image) {
        imageHtml = `
            <div class="mb-3 text-center rounded-3 overflow-hidden shadow-sm" style="max-height: 320px; background: #111;">
                <img src="${event.image}" alt="${event.title}" class="img-fluid w-100" style="max-height: 320px; object-fit: cover;">
            </div>
        `;
    }

    let descHtml = `
        <div class="event-modal-description mb-2" style="font-size: 1rem; line-height: 1.7; color: #333; white-space: pre-line;">
            ${event.description}
        </div>
    `;

    if (modalBody) {
        modalBody.innerHTML = `
            ${imageHtml}
            ${dateBadge}
            ${locationBadge}
            <hr class="my-3 opacity-25">
            ${descHtml}
        `;
    }

    if (modalLink) {
        let externalUrl = event.source_url;
        if (externalUrl && externalUrl !== '#' && !externalUrl.includes('meet-and-greet') && !externalUrl.includes('studio-tour') && !externalUrl.includes('charity-event')) {
            modalLink.href = externalUrl;
            modalLink.classList.remove('d-none');
            modalLink.innerHTML = `<i class="fas fa-external-link-alt me-1"></i> Visit Event Website`;
        } else if (externalUrl && (externalUrl.includes('meet-and-greet') || externalUrl.includes('studio-tour') || externalUrl.includes('charity-event'))) {
            modalLink.href = externalUrl;
            modalLink.classList.remove('d-none');
            modalLink.innerHTML = `<i class="fas fa-arrow-right me-1"></i> Full Event Page`;
        } else {
            modalLink.classList.add('d-none');
        }
    }

    const modalEl = document.getElementById('communityEventModal');
    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    }
};

// Load events on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCommunityEvents();
});
</script>
@endsection