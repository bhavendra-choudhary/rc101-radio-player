@extends('layouts.app')

@section('title', 'Contest Not Found')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-trophy fa-4x text-warning mb-4"></i>
                        <h1 class="h2 mb-3">No Contests Available</h1>
                        <p class="lead text-muted mb-4">We couldn't find any contests at the moment. Please check back later for upcoming contests and exciting opportunities to win amazing prizes!</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="{{ url('/') }}" class="btn btn-primary px-4">
                                <i class="fas fa-home me-2"></i>Return Home
                            </a>
                            <a href="#" class="btn btn-outline-secondary px-4" onclick="window.history.back()">
                                <i class="fas fa-arrow-left me-2"></i>Go Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
