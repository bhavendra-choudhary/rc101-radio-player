@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">{{ $ad->title }}</h2>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $imageUrl }}" alt="{{ $ad->title }}" class="img-fluid mb-4" style="max-height: 70vh; width: auto;">
                    
                    @if($ad->description)
                        <div class="ad-description text-start p-3 bg-light rounded">
                            {!! nl2br(e($ad->description)) !!}
                        </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
