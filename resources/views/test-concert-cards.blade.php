@extends('layouts.app')

@section('title', 'Test Concert Cards')

@section('content')
<div class="test-concert-cards-page">
    <div class="container">
        <!-- Header -->
        <div class="test-header">
            <h1>Concert Cards Style Test</h1>
            <p>This page displays the styling of concert cards for the Pop concerts feature.</p>
            <div class="location-badge">
                <i class="fas fa-map-marker-alt"></i>
                Location: {{ $location }}
            </div>
        </div>

        <!-- Concerts Grid - Using same structure as homepage -->
        <div class="row g-4" id="test-concerts-grid">
            @foreach($concerts as $concert)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 concert-card">
                    <img src="{{ $concert['image'] }}" 
                         class="card-img-top" 
                         alt="{{ $concert['name'] }}" 
                         style="height: 200px; object-fit: cover;"
                         onerror="this.src='https://via.placeholder.com/350x200/667eea/ffffff?text=Pop+Concert'">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">{{ $concert['name'] }}</h5>
                        <p class="card-text"><strong>{{ $concert['artist'] }}</strong></p>
                        <div class="mb-2">
                            <small class="text-muted d-block">
                                <i class="fas fa-calendar"></i> {{ $concert['date'] }}
                            </small>
                            @if($concert['time'])
                                <small class="text-muted d-block">
                                    <i class="fas fa-clock"></i> {{ date('g:i A', strtotime($concert['time'])) }}
                                </small>
                            @endif
                            <small class="text-muted d-block">
                                <i class="fas fa-map-marker-alt"></i> {{ $concert['venue']['name'] }}, {{ $concert['venue']['city'] }}
                            </small>
                            @if($concert['price_range'])
                                <small class="text-muted d-block">
                                    <i class="fas fa-tag"></i> {{ $concert['price_range'] }}
                                </small>
                            @endif
                        </div>
                        <div class="mt-auto">
                            <a href="{{ $concert['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-ticket-alt"></i> Get Tickets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Style Information -->
        <div class="style-info">
            <h2>Concert Card Features</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-image"></i>
                    <h3>Hero Image</h3>
                    <p>High-quality concert imagery with proper aspect ratio</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-music"></i>
                    <h3>Artist & Tour Info</h3>
                    <p>Prominent display of artist name and tour title</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Date & Time</h3>
                    <p>Clear formatting of event date and time</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-map-pin"></i>
                    <h3>Venue Details</h3>
                    <p>Venue name and location information</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>Price Range</h3>
                    <p>Clear pricing information for tickets</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-hand-pointer"></i>
                    <h3>Hover Effects</h3>
                    <p>Interactive hover animations and transitions</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Remove scrolling behavior from news section */
    .news-container {
        max-height: none !important;
        overflow: visible !important;
        overflow-y: visible !important;
        overflow-x: visible !important;
    }
    
    /* Ensure news items display properly without scroll */
    #news-container {
        max-height: none !important;
        overflow: visible !important;
    }
    
    /* Remove any scroll-related styling */
    .news-scroll-container {
        max-height: none !important;
        overflow: visible !important;
        overflow-y: visible !important;
    }
    
    /* Concert Card Styles - Same as homepage */
    .concert-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .concert-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }
    
    .concert-card .card-img-top {
        border-bottom: 3px solid #0d6efd;
    }
    
    .concert-card .card-title {
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }
    
    .concert-card .card-text {
        color: #e74c3c;
        font-weight: 500;
        margin-bottom: 1rem;
    }
    
    .concert-card .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .concert-card .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
        transform: translateY(-2px);
    }
    
    .concert-card .text-muted {
        font-size: 0.875rem;
        line-height: 1.4;
    }
    
    .concert-card .text-muted i {
        width: 16px;
        margin-right: 6px;
        color: #6c757d;
    }
    
    /* Test page specific styles */
    .test-concert-cards-page {
        min-height: 100vh;
        background: #f8fafc;
        padding: 40px 0;
    }
    
    .test-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .test-header h1 {
        color: #2d3748;
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .test-header p {
        color: #718096;
        font-size: 18px;
        margin-bottom: 25px;
    }
    
    .location-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #667eea;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
    }
    
    /* Style Info Section */
    .style-info {
        background: white;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
        margin-top: 50px;
    }
    
    .style-info h2 {
        color: #2d3748;
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
    }
    
    .feature-item {
        text-align: center;
        padding: 25px;
        background: #f7fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .feature-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .feature-item i {
        font-size: 36px;
        color: #667eea;
        margin-bottom: 15px;
    }
    
    .feature-item h3 {
        color: #2d3748;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .feature-item p {
        color: #718096;
        font-size: 14px;
        line-height: 1.5;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .test-concert-cards-page {
        padding: 20px 0;
    }
    
    .test-header h1 {
        font-size: 28px;
    }
    
    .test-header p {
        font-size: 16px;
    }
    
    .style-info {
        padding: 20px;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add some interactive features for testing
    const concertCards = document.querySelectorAll('.concert-card');
    
    concertCards.forEach(card => {
        // Add click animation
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.btn')) {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            }
        });
        
        // Add save button functionality
        const saveBtn = card.querySelector('.btn-outline-secondary');
        if (saveBtn) {
            saveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const icon = this.querySelector('i');
                if (icon.classList.contains('fas')) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    this.innerHTML = '<i class="far fa-heart"></i> Save';
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    this.innerHTML = '<i class="fas fa-heart"></i> Saved';
                }
            });
        }
    });
    
    // Log test information
    console.log('Concert Cards Test Page Loaded');
    console.log('Total concert cards:', concertCards.length);
    console.log('Grid layout:', window.getComputedStyle(document.querySelector('#test-concerts-grid')).display);
});
</script>
@endpush
