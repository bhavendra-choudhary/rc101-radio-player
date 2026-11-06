@extends('layouts.app')

@section('title', $contest->title ?? 'Contest')

@section('content')
    <div class="container py-5 contest-page-container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if(isset($contest))
                    <!-- Contest Header -->
                    <div class="text-center mb-5">
                        <h1 class="display-5 mb-3">{{ $contest->title }}</h1>
                        <p class="lead text-muted">{{ $contest->description }}</p>
                        
                        @if($contest->start_date && $contest->end_date)
                            <div class="contest-dates mt-4">
                                <span class="badge bg-primary p-2">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    {{ $contest->start_date->format('M d, Y') }} - {{ $contest->end_date->format('M d, Y') }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Contest Images Gallery -->
                    @if($hasImages)
                        <div class="contest-gallery mb-5">
                            <div class="row g-4">
                                @foreach($contest->images as $image)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 shadow-sm">
                                            @if($image->exists)
                                                <img src="{{ $image->url }}" 
                                                     class="card-img-top" 
                                                     alt="{{ $image->title ?? 'Contest Image' }}"
                                                     style="height: 200px; object-fit: cover;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                                     style="height: 200px;">
                                                    <div class="text-center p-3">
                                                        <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                                        <p class="mb-0 text-muted">Image not found</p>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if($image->title || $image->description)
                                                <div class="card-body">
                                                    @if($image->title)
                                                        <h5 class="card-title">{{ $image->title }}</h5>
                                                    @endif
                                                    @if($image->description)
                                                        <p class="card-text text-muted">{{ $image->description }}</p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Contest Details -->
                    <div class="card shadow-sm mb-5">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-4">Contest Details</h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h5 class="text-muted mb-2">Status</h5>
                                        @if($contest->is_active && $contest->start_date <= now() && $contest->end_date >= now())
                                            <span class="badge bg-success p-2">Active</span>
                                        @elseif($contest->start_date > now())
                                            <span class="badge bg-info p-2">Upcoming</span>
                                        @else
                                            <span class="badge bg-secondary p-2">Ended</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h5 class="text-muted mb-2">Duration</h5>
                                        <p class="mb-0">
                                            {{ $contest->start_date->format('F j, Y') }} - 
                                            {{ $contest->end_date->format('F j, Y') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            @if($contest->rules)
                                <div class="mt-4">
                                    <h5 class="text-muted mb-3">Contest Rules</h5>
                                    <div class="bg-light p-3 rounded">
                                        {!! nl2br(e($contest->rules)) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- No Active Contest -->
                    <div class="text-center py-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-5">
                                <i class="fas fa-trophy fa-4x text-muted mb-4"></i>
                                <h2 class="h3 mb-3">No Active Contest</h2>
                                <p class="text-muted mb-4">There are no active contests at the moment. Please check back later for upcoming contests and exciting opportunities to win VIP tickets!</p>
                                <a href="{{ url('/') }}" class="btn btn-primary px-4">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    @push('styles')
    <style>
        .contest-gallery .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .contest-gallery .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
        }
        .contest-dates .badge {
            font-size: 1rem;
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
        }
        
        /* Hide footer and all its content completely */
        footer {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            position: absolute !important;
            top: -9999px !important;
            left: -9999px !important;
            width: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        /* Hide footer container and all child elements */
        footer *,
        footer .container,
        footer .row,
        footer .col-md-4,
        footer .footer-column,
        footer .social-links,
        footer h3,
        footer p,
        footer a {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }
        
        /* Add proper spacing to prevent card overlap */
        .contest-page-container {
            padding-bottom: 150px;
            min-height: calc(100vh - 150px);
            margin-bottom: 50px;
        }
        
        /* Ensure body has proper spacing */
        body {
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }
        
        /* Hide any potential footer-related elements */
        .footer-column,
        .social-links,
        [class*="footer"] {
            display: none !important;
            visibility: hidden !important;
        }
    </style>
    @endpush
@endsection
