@extends('layouts.app')

@section('title', 'Listen Live')

@section('content')
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="brand-font">🎵 Listen Live</h1>
                    <p>Tune in to our live radio stream 24/7</p>
                    
                    <!-- Live Radio Player -->
                    <div class="text-center my-4">
                        <button class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-lg" id="hero-listen-btn-page" onclick="window.toggleLiveAudio()">
                            <i class="fas fa-play me-2" id="hero-listen-icon-page"></i> Listen Live Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
