@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Listeners List
        </a>
        <h2 class="fw-bold text-dark mb-1"><i class="fas fa-user-edit text-primary me-2"></i>Edit Listener Profile</h2>
        <p class="text-muted small mb-0">Update information and access status for listener #{{ $user->id }}</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <!-- Name -->
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                    </div>

                    <!-- City -->
                    <div class="col-md-6">
                        <label for="city" class="form-label fw-bold">City / Location</label>
                        <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $user->city) }}">
                    </div>

                    <!-- Account Status -->
                    <div class="col-md-6">
                        <label for="is_active" class="form-label fw-bold">Account Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="is_active" name="is_active" required>
                            <option value="1" {{ old('is_active', $user->is_active) ? 'selected' : '' }}>Active (Allowed Access)</option>
                            <option value="0" {{ !old('is_active', $user->is_active) ? 'selected' : '' }}>Disabled (Suspended Access)</option>
                        </select>
                    </div>

                    <!-- Registration Metadata -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted">Registered At</label>
                        <input type="text" class="form-control bg-light" value="{{ $user->created_at ? $user->created_at->format('F d, Y h:i A') : 'N/A' }}" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted">Last Updated</label>
                        <input type="text" class="form-control bg-light" value="{{ $user->updated_at ? $user->updated_at->format('F d, Y h:i A') : 'N/A' }}" readonly>
                    </div>

                    <div class="col-12 mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light border px-4">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
