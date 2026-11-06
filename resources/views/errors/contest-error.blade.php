@extends('layouts.app')

@section('title', 'Error Loading Contest')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-danger shadow">
                <div class="card-header bg-danger text-white">
                    <h1 class="h4 mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Oops! Something went wrong
                    </h1>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-ticket-alt fa-5x text-danger mb-4"></i>
                        <h2 class="h3 mb-3">We're having trouble loading this contest</h2>
                        <p class="lead text-muted mb-4">
                            We apologize for the inconvenience. Our team has been notified and we're working to fix the issue.
                        </p>
                    </div>

                    <div class="bg-light p-4 rounded mb-4">
                        <h3 class="h5 mb-3">What you can do:</h3>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-arrow-circle-right text-primary me-2"></i>
                                Refresh the page to try again
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-arrow-circle-right text-primary me-2"></i>
                                Check back in a few minutes
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-arrow-circle-right text-primary me-2"></i>
                                Browse our <a href="{{ url('/') }}">homepage</a> for other contests
                            </li>
                        </ul>
                    </div>

                    @if(isset($error) && config('app.debug'))
                        <div class="mt-4 p-3 bg-dark text-white rounded">
                            <h4 class="h6 text-uppercase text-muted mb-2">Technical Details</h4>
                            <code class="small">{{ $error }}</code>
                        </div>
                    @endif

                    <div class="d-flex justify-content-center gap-3 mt-5">
                        <a href="{{ url('/') }}" class="btn btn-primary px-4">
                            <i class="fas fa-home me-2"></i>Return Home
                        </a>
                        <button onclick="window.history.back()" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-arrow-left me-2"></i>Go Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
