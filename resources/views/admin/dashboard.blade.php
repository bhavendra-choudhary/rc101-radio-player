@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Dashboard</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Contests</h5>
                    <h2 class="mb-0">{{ $activeContests }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Contests</h5>
                    <h2 class="mb-0">{{ $totalContests }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Images</h5>
                    <h2 class="mb-0">{{ $totalImages }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-light mb-1">
                            <i class="fas fa-sliders-h"></i> Station Settings
                        </a>
                        <a href="{{ route('admin.contests.create') }}" class="btn btn-sm btn-light mb-1">
                            <i class="fas fa-plus"></i> New Contest
                        </a>
                        <a href="{{ route('admin.contests.index') }}" class="btn btn-sm btn-light">
                            <i class="fas fa-trophy"></i> Manage Contests
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Contests -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Contests</h5>
            <a href="{{ route('admin.contests.index') }}" class="btn btn-sm btn-primary">
                View All
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentContests as $contest)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.contests.show', $contest) }}">
                                        {{ $contest->title }}
                                    </a>
                                </td>
                                <td>
                                    @if($contest->is_active && $contest->end_date >= now() && $contest->start_date <= now())
                                        <span class="badge bg-success">Active</span>
                                    @elseif($contest->start_date > now())
                                        <span class="badge bg-info">Upcoming</span>
                                    @else
                                        <span class="badge bg-secondary">Ended</span>
                                    @endif
                                </td>
                                <td>{{ $contest->start_date->format('M d, Y') }}</td>
                                <td>{{ $contest->end_date->format('M d, Y') }}</td>
                                <td>{{ $contest->images_count ?? $contest->images->count() }}</td>
                                <td>
                                    <a href="{{ route('admin.contests.edit', $contest) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('admin.contests.show', $contest) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    No contests found. 
                                    <a href="{{ route('admin.contests.create') }}">Create your first contest</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
