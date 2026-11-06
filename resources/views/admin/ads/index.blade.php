@extends('admin.layout')

@section('title', 'Manage Ads')

@push('styles')
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-group .btn {
            margin-right: 5px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Ads</h1>
            <a href="{{ route('admin.ads.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Ad
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                @if($ads->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Title</th>
                                    <th>Target URL</th>
                                    <th>Status</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ads as $ad)
                                    <tr>
                                        <td>
                                            <img src="{{ $ad->image_url }}" alt="{{ $ad->title }}" style="max-width: 100px; max-height: 60px; object-fit: cover;">
                                        </td>
                                        <td>{{ $ad->title }}</td>
                                        <td>
                                            @if($ad->target_url)
                                                <a href="{{ $ad->target_url }}" target="_blank">{{ Str::limit($ad->target_url, 30) }}</a>
                                            @else
                                                <span class="text-muted">No link</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $ad->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $ad->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $ad->display_order }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.ads.edit', $ad) }}" class="btn btn-sm btn-outline-primary" style="height:30px;padding-top: 7px;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ad?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-primary" style="height: 30px;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-ad fa-4x text-muted mb-3"></i>
                        <h4>No ads found</h4>
                        <p class="text-muted">Get started by adding your first ad</p>
                        <a href="{{ route('admin.ads.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Ad
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
<style>
    .table th, .table td {
        vertical-align: middle;
    }
    .btn-group .btn {
        margin-right: 5px;
    }
</style>
@push('scripts')
    <script>
        // Add any additional scripts here
        document.addEventListener('DOMContentLoaded', function() {
            // Add any initialization code here
        });
    </script>
@endpush
