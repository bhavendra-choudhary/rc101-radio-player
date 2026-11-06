@extends('admin.layout')


@section('title', 'Contest Details - ' . (App\Models\Footer::getFooter()->brand_name ?? config('app.name')))

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="display-4">{{ $contest->title }}</h1>
                <div>
                    <a href="{{ route('admin.contests.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Back to Contests
                    </a>
                    <a href="{{ route('admin.contests.edit', $contest) }}" class="btn btn-warning me-2">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('admin.contests.upload', $contest) }}" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Images
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h3 class="mb-0">Contest Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h5>Status</h5>
                                @if($contest->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </div>
                            <div class="mb-3">
                                <h5>Duration</h5>
                                <p>{{ $contest->start_date->format('M d, Y') }} - {{ $contest->end_date->format('M d, Y') }}</p>
                            </div>
                            <div class="mb-3">
                                <h5>Description</h5>
                                <p>{{ $contest->description ?? 'No description provided.' }}</p>
                            </div>
                            <div class="mb-3">
                                <h5>Created</h5>
                                <p>{{ $contest->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="mb-3">
                                <h5>Last Updated</h5>
                                <p>{{ $contest->updated_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Contest Images</h3>
                            <a href="{{ route('admin.contests.upload', $contest) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-upload"></i> Upload
                            </a>
                        </div>
                        <div class="card-body">
                            @if($contest->images->count() > 0)
                                <div class="row">
                                    @foreach($contest->images as $image)
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card h-100">
                                                <img src="{{ asset('storage/' . $image->image_path) }}" class="card-img-top" alt="{{ $image->title ?? 'Contest image' }}" style="height: 200px; object-fit: cover;">
                                                <div class="card-body">
                                                    <h5 class="card-title">{{ $image->title ?? 'Untitled' }}</h5>
                                                    <p class="card-text small">{{ Str::limit($image->description ?? 'No description', 100) }}</p>
                                                </div>
                                                <div class="card-footer bg-white d-flex justify-content-between">
                                                    <small class="text-muted">Order: {{ $image->display_order }}</small>
                                                    <form action="{{ route('admin.contest-images.destroy', $image) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this image?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                    <p class="lead">No images uploaded yet</p>
                                    <a href="{{ route('admin.contests.upload', $contest) }}" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Upload Images
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection