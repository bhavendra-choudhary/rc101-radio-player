@extends('layouts.app')

@section('title', 'Pop Concerts Near You - ' . (App\Models\Footer::getFooter()->brand_name ?? config('app.name')))

@section('content')
<div class="pop-concerts-page">
    <!-- Hero Section -->
    <div class="concerts-hero">
        <div class="hero-content">
            <h1>Pop Concerts Near You</h1>
            <p>Discover amazing Pop concerts happening within 50 miles of your location</p>
        </div>
    </div>
    
    <!-- Location Concerts Component -->
    <div class="concerts-component-container">
        @include('components.location-concerts')
    </div>
    
    <!-- Additional Features Section -->
    <div class="features-section">
        <div class="container">
            <h2>Why Use Our Concert Finder?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Location-Based</h3>
                    <p>Automatically finds concerts near your current location or search by city</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <h3>Pop Music Focus</h3>
                    <p>Specialized in Pop music events from your favorite artists</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3>Direct Ticket Links</h3>
                    <p>Get tickets directly from Ticketmaster with official pricing</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Real-Time Updates</h3>
                    <p>Fresh concert data with venue information and pricing</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Popular Cities Section -->
    <div class="popular-cities-section">
        <div class="container">
            <h2>Popular Cities</h2>
            <p>Quick access to Pop concerts in major cities</p>
            <div class="cities-grid">
                <a href="#" class="city-card" data-city="New York">
                    <i class="fas fa-city"></i>
                    <span>New York</span>
                </a>
                <a href="#" class="city-card" data-city="Los Angeles">
                    <i class="fas fa-city"></i>
                    <span>Los Angeles</span>
                </a>
                <a href="#" class="city-card" data-city="Chicago">
                    <i class="fas fa-city"></i>
                    <span>Chicago</span>
                </a>
                <a href="#" class="city-card" data-city="Miami">
                    <i class="fas fa-city"></i>
                    <span>Miami</span>
                </a>
                <a href="#" class="city-card" data-city="Seattle">
                    <i class="fas fa-city"></i>
                    <span>Seattle</span>
                </a>
                <a href="#" class="city-card" data-city="Denver">
                    <i class="fas fa-city"></i>
                    <span>Denver</span>
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.pop-concerts-page {
    min-height: 100vh;
    background: #f8fafc;
}

.concerts-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 20px;
    text-align: center;
}

.hero-content h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 15px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.hero-content p {
    font-size: 1.25rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.concerts-component-container {
    margin: -40px auto 60px;
    position: relative;
    z-index: 10;
}

.features-section {
    padding: 80px 20px;
    background: white;
}

.features-section h2 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 50px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.feature-card {
    text-align: center;
    padding: 30px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.feature-icon {
    font-size: 3rem;
    color: #667eea;
    margin-bottom: 20px;
}

.feature-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 15px;
}

.feature-card p {
    color: #718096;
    line-height: 1.6;
}

.popular-cities-section {
    padding: 80px 20px;
    background: #f8fafc;
}

.popular-cities-section h2 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 15px;
}

.popular-cities-section p {
    text-align: center;
    color: #718096;
    margin-bottom: 40px;
    font-size: 1.1rem;
}

.cities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.city-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    text-decoration: none;
    color: #2d3748;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    font-weight: 500;
}

.city-card:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.city-card i {
    font-size: 1.5rem;
    color: #667eea;
    transition: color 0.2s ease;
}

.city-card:hover i {
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .concerts-hero {
        padding: 60px 20px;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-content p {
        font-size: 1rem;
    }
    
    .concerts-component-container {
        margin: -30px 20px 40px;
    }
    
    .features-section,
    .popular-cities-section {
        padding: 60px 20px;
    }
    
    .features-section h2,
    .popular-cities-section h2 {
        font-size: 2rem;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .cities-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .hero-content h1 {
        font-size: 1.75rem;
    }
    
    .feature-card {
        padding: 20px;
    }
    
    .city-card {
        padding: 15px;
        font-size: 0.9rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle city card clicks
    const cityCards = document.querySelectorAll('.city-card');
    
    cityCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const cityName = this.getAttribute('data-city');
            
            // Find the manual location input and trigger search
            const cityInput = document.querySelector('input[id$="-city-input"]');
            const searchBtn = document.querySelector('button[id$="-search-city"]');
            const manualBtn = document.querySelector('button[id$="-manual-location"]');
            
            if (cityInput && searchBtn && manualBtn) {
                // Switch to manual location mode
                manualBtn.click();
                
                // Set city name and search
                setTimeout(() => {
                    cityInput.value = cityName;
                    searchBtn.click();
                }, 100);
            }
        });
    });
});
</script>
@endpush
@endsection
