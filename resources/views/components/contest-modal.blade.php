@php
    use App\Models\Contest;

    $activeContests = Contest::query()
        ->where('is_active', true)
        ->whereDate('start_date', '<=', today())
        ->whereDate('end_date', '>=', today())
        ->with([
            'images' => function ($query) {
                $query->orderBy('display_order', 'asc');
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get()
        ->each(function ($contest) {
            $contest->images->each(function ($image) {
                $image->url = asset('storage/' . ltrim($image->image_path, '/'));
            });

            // Check if contest has any images
            $contest->has_images = $contest->images->isNotEmpty();
        });

    $hasContests = $activeContests->isNotEmpty();
@endphp

<!-- Contest Section -->
<section class="contest-section section-bg" id="contest-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-4">
                    <h3 class="display-5">
                        <i class="fas fa-trophy me-2 text-warning"></i>
                        Featured Contests
                    </h3>
                    <p class="lead text-muted mt-2">
                        Enter for a chance to win exclusive tickets, passes, and merchandise.
                    </p>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        @if(!$hasContests)
            <div id="contestEmpty" class="text-center py-5">
                <i class="fas fa-trophy text-muted" style="font-size: 3rem;"></i>
                <h4 class="mt-3 text-muted">No Active Contests</h4>
                <p class="text-muted">Check back soon for exciting contest opportunities!</p>
            </div>
        @endif

        <!-- Contest Grid -->
        @if($hasContests)
            <div id="contestGrid" class="row g-4 justify-content-center">
                @foreach($activeContests as $contest)
                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                        <div class="contest-card card h-100 border-0 shadow-sm" style="padding-bottom:10px;padding-left:10px;">
                            <!-- Image Section - Carousel -->
                            <div class="contest-image-container">
                                @if($contest->has_images)
                                    <div class="image-carousel" data-carousel-id="{{ $contest->id }}">
                                        <div class="carousel-inner1">
                                            @foreach($contest->images as $index => $image)
                                                <div class="carousel-item @if($index === 0) active @endif">
                                                    <img style="max-height: 185px;" src="{{ $image->url }}" 
                                                         alt="{{ $contest->title }} - Image {{ $index + 1 }}"
                                                         class="carousel-image"
                                                         onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="image-fallback" style="display: none;">
                                                        <i class="fas fa-image text-muted"></i>
                                                        <span class="d-block mt-2 text-muted">Image {{ $index + 1 }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="contest-no-image">
                                        <i class="fas fa-image text-muted"></i>
                                        <span class="d-block mt-2 text-muted">No Image</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Card Body -->
                            <div class="contest-card-body" style="padding:10px;">
                                <h5 class="contest-card-title">{{ $contest->title }}</h5>

                                <!-- <div class="contest-meta mb-3">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar-alt text-primary"></i>
                                        <span>Ends: {{ $contest->end_date->format('M d, Y') }}</span>
                                    </div>
                                    @if($contest->images->count() > 1)
                                    <div class="meta-item">
                                        <i class="fas fa-images text-primary"></i>
                                        <span>{{ $contest->images->count() }} Images</span>
                                    </div>
                                    @endif
                                </div> -->

                                <p class="contest-card-description">
                                    <!-- {{ Str::limit($contest->description, 100) }} -->
                                    {{ $contest->description }}
                                </p>
                            </div>

                            <!-- Card Footer -->
                            <div class="contest-card-footer">
                                @if($contest->url)
                                    <a href="{{ $contest->url }}" 
                                       class="btn btn-primary btn-block contest-action-btn"
                                       target="_blank"
                                       onclick="trackContestClick({{ $contest->id }})">
                                        <i class="fas fa-external-link-alt me-2"></i>View Contest
                                    </a>
                                @else
                                    <button class="btn btn-outline-primary btn-block contest-action-btn">
                                        <i class="fas fa-info-circle me-2"></i>View Details
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@push('styles')
    <style>
    .contest-section {
        margin: 40px 0;
        padding: 40px 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        scroll-margin-top: 80px;
    }

    /* Card Design */
    .contest-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #e0e0e0;
        max-width: 380px;
        margin-left: auto;
        margin-right: auto;
    }

    .contest-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-color: #4a6cf7;
    }

    /* Image Container - Carousel */
    .contest-image-container {
        position: relative;
        height: 200px;
        overflow: hidden;
        background: #f5f7fa;
        width: 100%;
        padding: 0;
        margin: 0;
    }

    .image-carousel {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .carousel-inner1 {
        position: relative;
        width: 100%;
        max-height: 220px;
    }

    .carousel-item {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 0.8s ease-in-out;
        z-index: 1;
    }

    .carousel-item.active {
        opacity: 1;
        z-index: 2;
    }

    .carousel-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }

    .image-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f1f3f9 0%, #e4e8f7 100%);
    }

    .image-fallback i {
        font-size: 2rem;
        opacity: 0.5;
    }

    .image-fallback span {
        font-size: 0.8rem;
        opacity: 0.7;
    }

    /* No Image State */
    .contest-no-image {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f1f3f9 0%, #e4e8f7 100%);
    }

    .contest-no-image i {
        font-size: 3rem;
        opacity: 0.5;
    }

    .contest-no-image span {
        font-size: 0.9rem;
        opacity: 0.7;
    }

    /* Card Body */
    .contest-card-body {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .contest-card-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 12px;
        color: #2c3e50;
        line-height: 1.4;
    }

    .contest-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 15px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .meta-item i {
        font-size: 0.8rem;
    }

    .contest-card-description {
        color: #5d6c83;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 15px;
        flex: 1;
    }

    /* Card Footer */
    .contest-card-footer {
        padding: 0 20px 20px;
    }

    .contest-action-btn {
        width: 100%;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        cursor: pointer;
    }

    .contest-action-btn.btn-primary {
        background: linear-gradient(135deg, #4a6cf7 0%, #6a82fb 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(74, 108, 247, 0.2);
    }

    .contest-action-btn.btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(74, 108, 247, 0.3);
        background: linear-gradient(135deg, #3a5ce5 0%, #5a72eb 100%);
        color: white;
    }

    .contest-action-btn.btn-outline-primary {
        background: transparent;
        border: 2px solid #4a6cf7;
        color: #4a6cf7;
    }

    .contest-action-btn.btn-outline-primary:hover {
        background: #4a6cf7;
        color: white;
        transform: translateY(-2px);
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .contest-section {
            padding: 30px 0;
            margin: 30px 0;
        }
    }

    @media (max-width: 768px) {
        .contest-section {
            margin: 20px 0;
            padding: 20px 0;
            scroll-margin-top: 60px;
        }

        .contest-image-container {
            height: 180px;
        }

        .contest-card {
            max-width: 100%;
        }

        .contest-card-title {
            font-size: 1.1rem;
        }

        .contest-card-description {
            font-size: 0.85rem;
        }

        .contest-action-btn {
            padding: 10px 16px;
            font-size: 0.9rem;
        }

        .meta-item {
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .contest-image-container {
            height: 160px;
        }

        .contest-card {
            max-width: 100%;
        }
    }

    </style>
@endpush

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all image carousels
        document.querySelectorAll('.image-carousel').forEach(carousel => {
            const carouselId = carousel.getAttribute('data-carousel-id');
            const carouselItems = carousel.querySelectorAll('.carousel-item');

            // If no items or only one item, return
            if (carouselItems.length <= 1) return;

            let currentIndex = 0;
            let slideInterval;
            const slideDuration = 4000; // 4 seconds

            // Function to show next slide
            function showNextSlide() {
                // Remove active class from current slide
                carouselItems[currentIndex].classList.remove('active');

                // Calculate next index
                currentIndex = (currentIndex + 1) % carouselItems.length;

                // Add active class to next slide
                carouselItems[currentIndex].classList.add('active');
            }

            // Start automatic sliding
            function startCarousel() {
                stopCarousel(); // Clear any existing interval
                slideInterval = setInterval(showNextSlide, slideDuration);
            }

            // Stop sliding
            function stopCarousel() {
                if (slideInterval) {
                    clearInterval(slideInterval);
                }
            }

            // Pause on hover
            carousel.addEventListener('mouseenter', stopCarousel);
            carousel.addEventListener('mouseleave', startCarousel);

            // Start the carousel
            startCarousel();
        });

        // Make View Details button open URL if it exists
        document.querySelectorAll('.contest-action-btn.btn-outline-primary').forEach(button => {
            button.addEventListener('click', function(e) {
                const card = this.closest('.contest-card');
                const urlLink = card.querySelector('a.contest-action-btn[href]');
                if (urlLink) {
                    const url = urlLink.getAttribute('href');
                    if (url && url !== '#') {
                        window.open(url, '_blank');
                    }
                }
            });
        });
    });

    // Track contest clicks for analytics
    function trackContestClick(contestId) {
        // Analytics hook

        // You can implement analytics tracking here
        // Example:
        // fetch('/api/contest/' + contestId + '/click', {
        //     method: 'POST',
        //     headers: {
        //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        //         'Content-Type': 'application/json'
        //     }
        // }).catch(error => console.error('Error tracking click:', error));
    }
    </script>
@endpush