@extends('admin.layout')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">
    <!-- Header Title & Export Button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fas fa-users text-primary me-2"></i>Listener Management</h2>
            <p class="text-muted small mb-0">View, search, edit, disable, delete, and export registered listener accounts.</p>
        </div>
        <a href="{{ route('admin.users.export', request()->query()) }}" class="btn btn-outline-success btn-sm-block">
            <i class="fas fa-file-export me-1"></i> Export Listeners CSV
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 p-md-3 me-2 me-md-3 text-primary">
                        <i class="fas fa-user-friends fa-lg fa-md-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted extra-small fw-bold text-uppercase">Total Listeners</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-2 p-md-3 me-2 me-md-3 text-success">
                        <i class="fas fa-user-check fa-lg fa-md-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted extra-small fw-bold text-uppercase">Active Accounts</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-2 p-md-3 me-2 me-md-3 text-danger">
                        <i class="fas fa-user-slash fa-lg fa-md-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted extra-small fw-bold text-uppercase">Disabled Accounts</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['inactive'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-2 p-md-3 me-2 me-md-3 text-info">
                        <i class="fas fa-user-plus fa-lg fa-md-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted extra-small fw-bold text-uppercase">New (Last 7 Days)</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['recent'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-light" placeholder="Search by name, phone number, or city..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select border-0 bg-light">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled Only</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fas fa-undo"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Desktop View: Listeners Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Name</th>
                            <th>Phone Number</th>
                            <th>City / Location</th>
                            <th>Registered Date</th>
                            <th>Last Activity</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($listeners as $listener)
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#{{ $listener->id }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $listener->name }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-normal"><i class="fas fa-phone-alt me-1 text-primary"></i>{{ $listener->phone }}</span>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>{{ $listener->city ?? 'Not specified' }}
                                </td>
                                <td class="small text-muted">
                                    {{ $listener->created_at ? $listener->created_at->format('M d, Y h:i A') : 'N/A' }}
                                </td>
                                <td class="small text-muted">
                                    {{ $listener->updated_at ? $listener->updated_at->diffForHumans() : 'N/A' }}
                                </td>
                                <td>
                                    @if($listener->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1"><i class="fas fa-check-circle me-1"></i>Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1"><i class="fas fa-ban me-1"></i>Disabled</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.users.edit', $listener) }}" class="btn btn-sm btn-outline-primary" title="Edit Listener">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.users.toggle-status', $listener) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $listener->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $listener->is_active ? 'Disable Account' : 'Enable Account' }}">
                                                <i class="fas {{ $listener->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i> {{ $listener->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.users.destroy', $listener) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this listener account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Listener">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-user-friends text-muted fa-3x mb-3 d-block"></i>
                                    <h5 class="text-muted">No registered listeners found.</h5>
                                    @if(request('search') || request('status'))
                                        <p class="text-muted small">Try adjusting your search criteria or filters.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($listeners->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                {{ $listeners->links() }}
            </div>
        @endif
    </div>

    <!-- Mobile View: Card Stack -->
    <div class="d-md-none">
        @forelse($listeners as $listener)
            <div class="card border-0 shadow-sm rounded-3 mb-3 p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-light text-muted border">#{{ $listener->id }}</span>
                        <h6 class="fw-bold text-dark d-inline ms-1 mb-0">{{ $listener->name }}</h6>
                    </div>
                    @if($listener->is_active)
                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1"><i class="fas fa-check-circle me-1"></i>Active</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1"><i class="fas fa-ban me-1"></i>Disabled</span>
                    @endif
                </div>

                <div class="small text-muted mb-2">
                    <div><i class="fas fa-phone-alt text-primary me-2"></i>{{ $listener->phone }}</div>
                    <div><i class="fas fa-map-marker-alt text-danger me-2"></i>{{ $listener->city ?? 'Not specified' }}</div>
                    <div><i class="fas fa-calendar-alt text-secondary me-2"></i>Registered: {{ $listener->created_at ? $listener->created_at->format('M d, Y') : 'N/A' }}</div>
                    <div><i class="fas fa-clock text-info me-2"></i>Active: {{ $listener->updated_at ? $listener->updated_at->diffForHumans() : 'N/A' }}</div>
                </div>

                <div class="d-flex gap-2 mt-3 pt-2 border-top">
                    <a href="{{ route('admin.users.edit', $listener) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    <form action="{{ route('admin.users.toggle-status', $listener) }}" method="POST" class="flex-grow-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm w-100 {{ $listener->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                            <i class="fas {{ $listener->is_active ? 'fa-user-slash' : 'fa-user-check' }} me-1"></i> {{ $listener->is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                    <form action="{{ route('admin.users.destroy', $listener) }}" method="POST" onsubmit="return confirm('Delete this listener account?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card border-0 shadow-sm p-4 text-center">
                <i class="fas fa-user-friends text-muted fa-2x mb-2"></i>
                <p class="text-muted mb-0">No registered listeners found.</p>
            </div>
        @endforelse

        @if($listeners->hasPages())
            <div class="mt-3">
                {{ $listeners->links() }}
            </div>
        @endif
    </div>
</div>

<style>
.extra-small { font-size: 0.7rem; }

/* Pagination Styles */
.pagination {
    margin-bottom: 0;
    gap: 4px;
}
.pagination .page-item .page-link {
    border-radius: 6px !important;
    font-size: 0.875rem;
    padding: 6px 12px;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    background: #fff;
    transition: all 0.2s ease;
    line-height: 1.4;
}
.pagination .page-item.active .page-link {
    background: #6366f1;
    border-color: #6366f1;
    color: #fff;
    font-weight: 600;
}
.pagination .page-item.disabled .page-link {
    color: #d1d5db;
    background: #f9fafb;
    border-color: #e5e7eb;
    cursor: not-allowed;
}
.pagination .page-item .page-link:hover:not(.active) {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #111827;
}
</style>
@endsection
