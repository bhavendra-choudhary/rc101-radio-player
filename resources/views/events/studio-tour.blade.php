@extends('layouts.app')

@section('title', 'Studio Tour')

@section('content')
    <div class="container py-5 mt-5" style="min-height: 70vh;">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow border-0 bg-dark text-white rounded-4 overflow-hidden">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-broadcast-tower fa-4x text-info mb-4"></i>
                        <h1 class="display-5 fw-bold mb-4">Studio Tour</h1>
                        <p class="lead text-light mb-4">
                            Get a behind-the-scenes look at our broadcasting facilities and see how radio magic happens.
                        </p>
                        <hr class="border-secondary my-4">
                        <div class="text-start">
                            <h3 class="h4 text-info mb-3">Tour Highlights</h3>
                            <p class="mb-4">
                                Have you ever wondered what goes on inside a real radio station? Our exclusive studio tours offer listeners a chance to walk through the actual broadcast booths, learn about the audio equipment, and even record your own station drop!
                            </p>
                            
                            <h4 class="h5 text-white mb-3"><i class="fas fa-eye text-info me-2"></i> What You'll See</h4>
                            <ul class="list-group list-group-flush bg-transparent mb-4">
                                <li class="list-group-item bg-transparent text-light border-secondary"><i class="fas fa-microphone-alt text-success me-2"></i> The Main Live Studio Matrix</li>
                                <li class="list-group-item bg-transparent text-light border-secondary"><i class="fas fa-sliders-h text-success me-2"></i> Production & Editing Bays</li>
                                <li class="list-group-item bg-transparent text-light border-secondary border-bottom-0"><i class="fas fa-record-vinyl text-success me-2"></i> The {{ App\Models\Footer::getFooter()->brand_name ?? config('app.name') }} Music Library Archive</li>
                            </ul>
                        </div>
                        <div class="mt-5">
                            <a href="{{ url('/#events') }}" class="btn btn-outline-light px-4 py-2 rounded-pill shadow-sm transition-all hover-info">
                                <i class="fas fa-arrow-left me-2"></i> Back to Events
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .hover-info:hover {
            background-color: var(--bs-info, #0dcaf0) !important;
            border-color: var(--bs-info, #0dcaf0) !important;
            color: black !important;
        }
    </style>
@endsection
