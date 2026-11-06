@extends('layouts.app')

@section('title', 'Meet & Greet')

@section('content')
    <div class="container py-5 mt-5" style="min-height: 70vh;">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow border-0 bg-dark text-white rounded-4 overflow-hidden">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-users fa-4x text-primary mb-4"></i>
                        <h1 class="display-5 fw-bold mb-4">Meet & Greet</h1>
                        <p class="lead text-light mb-4">
                            Meet your favorite radio hosts and win exclusive merchandise at our monthly event.
                        </p>
                        <hr class="border-secondary my-4">
                        <div class="text-start">
                            <h3 class="h4 text-primary mb-3">Event Details</h3>
                            <p class="mb-4">
                                Join us at the {{ App\Models\Footer::getFooter()->brand_name ?? config('app.name') }} studios on the first Saturday of every month for a chance to interact with the voices behind the mic! Our Meet & Greet events feature live broadcasts, exclusive giveaways, behind-the-scenes access, and photo opportunities with our top DJs.
                            </p>
                            
                            <h4 class="h5 text-white mb-3"><i class="fas fa-calendar-alt text-primary me-2"></i> Upcoming Schedule</h4>
                            <ul class="list-group list-group-flush bg-transparent mb-4">
                                <li class="list-group-item bg-transparent text-light border-secondary"><i class="fas fa-check text-success me-2"></i> <strong>March 4th:</strong> Morning Show Team</li>
                                <li class="list-group-item bg-transparent text-light border-secondary"><i class="fas fa-check text-success me-2"></i> <strong>April 1st:</strong> Afternoon Drive Team</li>
                                <li class="list-group-item bg-transparent text-light border-secondary border-bottom-0"><i class="fas fa-check text-success me-2"></i> <strong>May 6th:</strong> Weekend Mixers</li>
                            </ul>
                        </div>
                        <div class="mt-5">
                            <a href="{{ url('/#events') }}" class="btn btn-outline-light px-4 py-2 rounded-pill shadow-sm transition-all hover-primary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Events
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .hover-primary:hover {
            background-color: var(--primary-color, #ff4757) !important;
            border-color: var(--primary-color, #ff4757) !important;
            color: white !important;
        }
    </style>
@endsection
