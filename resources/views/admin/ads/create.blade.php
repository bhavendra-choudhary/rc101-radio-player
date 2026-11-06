@extends('admin.layout')

@section('title', 'Create New Ad')

@push('styles')
<style>
    .preview-image {
        max-width: 300px;
        max-height: 200px;
        margin-top: 10px;
        display: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Create New Ad</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ads.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Ad Title *</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Ad Image *</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                   id="image" name="image" accept="image/*" required>
                            <div class="form-text">
                                <strong>Recommended Resolution:</strong> 1200 &times; 300 px (Banner / Leaderboard, 4:1 ratio) or 1200 &times; 630 px (Standard Banner).<br>
                                <strong>Minimum size:</strong> 800 &times; 300 px<br>
                                <strong>Maximum file size:</strong> 10MB<br>
                                <strong>Formats:</strong> JPG, PNG, GIF, or WebP
                            </div>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-2">
                                <img id="image-preview" src="#" alt="Preview" style="max-width: 100%; display: none;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="target_url" class="form-label">Target URL</label>
                            <input type="url" class="form-control @error('target_url') is-invalid @enderror" 
                                   id="target_url" name="target_url" value="{{ old('target_url') }}" placeholder="https://example.com">
                            @error('target_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="display_order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control @error('display_order') is-invalid @enderror" 
                                           id="display_order" name="display_order" value="{{ old('display_order', 0) }}" min="0">
                                    @error('display_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.ads.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Create Ad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('image');
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('image-preview');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.src = '';
                    preview.style.display = 'none';
                }
            });
        }
    });
</script>
@endsection
