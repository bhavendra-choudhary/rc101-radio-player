@extends('layouts.app')

@section('title', 'Charity Event')

@section('content')
    <div class="container py-5 mt-5" style="min-height: 70vh;">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow border-0 bg-dark text-white rounded-4 overflow-hidden">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-calendar-check fa-4x text-success mb-4"></i>
                        <h1 class="display-5 fw-bold mb-4">Charity Event</h1>
                        <p class="lead text-light mb-4">
                            Join us for our annual charity event supporting music education in local schools.
                        </p>
                        <hr class="border-secondary my-4">
                        <div class="text-start">
                            <h3 class="h4 text-success mb-3">Community Impact</h3>
                            <p class="mb-4">
                                At {{ App\Models\Footer::getFooter()->brand_name ?? config('app.name') }}, we believe in giving back to the community that supports us. Every year, we host a major charity fundraiser dedicated to keeping music arts alive in our local public schools. Your participation goes directly to funding instruments, sheet music, and instructional resources for students.
                            </p>
                            
                            <h4 class="h5 text-white mb-3"><i class="fas fa-hand-holding-heart text-success me-2"></i> How to Get Involved</h4>
                            <ul class="list-group list-group-flush bg-transparent mb-4">
                                <li class="list-group-item bg-transparent text-light border-secondary"><i class="fas fa-donate text-warning me-2"></i> <strong>Donate:</strong> Sponsor a student's musical journey</li>
                                <li class="list-group-item bg-transparent text-light border-secondary"><i class="fas fa-hands-helping text-warning me-2"></i> <strong>Volunteer:</strong> Help set up and run the annual gala</li>
                                <li class="list-group-item bg-transparent text-light border-secondary border-bottom-0"><i class="fas fa-guitar text-warning me-2"></i> <strong>Perform:</strong> Showcase your local band's talent</li>
                            </ul>
                        </div>
                        <div class="mt-5">
                            <a href="{{ url('/#events') }}" class="btn btn-outline-light px-4 py-2 rounded-pill shadow-sm transition-all hover-success">
                                <i class="fas fa-arrow-left me-2"></i> Back to Events
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .hover-success:hover {
            background-color: var(--bs-success, #198754) !important;
            border-color: var(--bs-success, #198754) !important;
            color: white !important;
        }
    </style>
@endsection
