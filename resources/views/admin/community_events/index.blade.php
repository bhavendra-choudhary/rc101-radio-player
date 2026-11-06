@extends('admin.layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Community Events</h2>
        <a href="{{ route('admin.community-events.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Community Event
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($events->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Event Date</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($events as $event)
                                <tr>
                                    <td>{{ $event->id }}</td>
                                    <td>
                                        @if($event->image)
                                            <img src="{{ asset('storage/' . $event->image) }}" 
                                                 alt="{{ $event->title }}" 
                                                 class="img-thumbnail" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $event->title }}</strong>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($event->description, 100) }}</small>
                                    </td>
                                    <td>{{ $event->event_date->format('M d, Y') }}</td>
                                    <td>{{ $event->location }}</td>
                                    <td>
                                        @if($event->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif($event->status === 'inactive')
                                            <span class="badge bg-secondary">Inactive</span>
                                        @else
                                            <span class="badge bg-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $event->display_order }}</td>
                                    <td>
                                        <div class="btn-group gap-2" role="group">
                                            <a href="{{ route('admin.community-events.edit', $event) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary toggle-status-btn"
                                                    data-id="{{ $event->id }}"
                                                    data-current-status="{{ $event->status }}"
                                                    data-url="{{ route('admin.community-events.toggle-status', $event->id) }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <form action="{{ route('admin.community-events.destroy', $event) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-primary"
                                                        onclick="return confirm('Are you sure you want to delete this event?')">
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
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No community events found</h5>
                    <p class="text-muted">Start by adding your first community event.</p>
                    <a href="{{ route('admin.community-events.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Community Event
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle toggle status
        document.querySelectorAll('.toggle-status-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const currentStatus = this.dataset.currentStatus;

                fetch(`/admin/community-events/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to show updated status
                        location.reload();
                    } else {
                        alert('Error updating status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status. Please try again.');
                });
            });
        });
    });
    </script>
@endpush
