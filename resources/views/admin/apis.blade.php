@extends('admin.layout')

@section('title', 'API Management - ' . (App\Models\Footer::getFooter()->brand_name ?? config('app.name')))

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="display-4">🔧 API Management</h1>
                    <div>
                        <a href="{{ route('admin.contests.index') }}" class="btn btn-outline-primary me-2">Contest Management</a>
                        <a href="/" class="btn btn-outline-secondary">← Back to Home</a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Validation Error:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Security Notice & Options -->
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <i class="fas fa-shield-alt"></i>
                            <strong>Security Notice:</strong> API keys are securely stored and hidden by default.
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="showSecurityOptions()">
                                <i class="fas fa-cog"></i> Security Options
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Security Options Panel (Hidden by default) -->
                <div class="card mb-3" id="securityOptionsPanel" style="display: none;">
                    <div class="card-header">
                        <h5><i class="fas fa-shield-alt"></i> Security Options</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="autoHideValues" checked>
                                    <label class="form-check-label" for="autoHideValues">
                                        Auto-hide values after 30 seconds
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="showFullValues">
                                    <label class="form-check-label" for="showFullValues">
                                        Show full values (instead of masked)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    These settings only affect the current session and are not saved.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit API Form -->
                <div class="card mb-5">
                    <div class="card-header">
                        <h3>Add/Update API Configuration</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.apis.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">API Name</label>
                                    <select class="form-select" id="name" name="name" required>
                                        <option value="">Select API Type</option>
                                        <option value="news_api">News API</option>
                                        <option value="bandsintown_api">Bandsintown API</option>
                                        <option value="spotify_api">Spotify API</option>
                                        <option value="youtube_api">YouTube API</option>
                                        <option value="lastfm_api">Last.fm API</option>
                                        <option value="ticketmaster_api">Ticketmaster API</option>
                                    </select>
                                    @error('name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="api_key">API Key</option>
                                        <option value="endpoint">Endpoint URL</option>
                                        <option value="token">Access Token</option>
                                        <option value="secret">Secret Key</option>
                                    </select>
                                    @error('type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="value" class="form-label">API Key/URL/Token</label>
                                <textarea class="form-control" id="value" name="value" rows="3"
                                    placeholder="Paste your API key, endpoint URL, or token here..."
                                    required minlength="10"></textarea>
                                <div class="form-text">
                                    <i class="fas fa-shield-alt text-warning"></i>
                                    API values are securely stored and masked in the interface.
                                </div>
                                @error('value')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description (Optional)</label>
                                <input type="text" class="form-control" id="description" name="description" 
                                    placeholder="Brief description of this API...">
                                @error('description')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary" id="saveBtn">💾 Save API</button>
                        </form>
                    </div>
                </div>

                <script>
                    // Client-side validation
                    document.getElementById('value').addEventListener('input', function() {
                        const value = this.value.trim();
                        const saveBtn = document.getElementById('saveBtn');

                        if (value.length < 10) {
                            this.classList.add('is-invalid');
                            saveBtn.disabled = true;
                        } else {
                            this.classList.remove('is-invalid');
                            saveBtn.disabled = false;
                        }
                    });

                    // Form submission validation
                    document.querySelector('form').addEventListener('submit', function(e) {
                        const value = document.getElementById('value').value.trim();

                        if (value.length < 10) {
                            e.preventDefault();
                            alert('API value must be at least 10 characters long.');
                            return false;
                        }

                        if (!value) {
                            e.preventDefault();
                            alert('API value cannot be empty.');
                            return false;
                        }
                    });

                    // Toggle API values visibility
                    function toggleApiValues() {
                        const checkbox = document.getElementById('showApiValues');
                        const hiddenElements = document.querySelectorAll('.api-value-hidden');
                        const shownElements = document.querySelectorAll('.api-value-shown');

                        if (checkbox.checked) {
                            // Show masked values
                            hiddenElements.forEach(el => el.style.display = 'none');
                            shownElements.forEach(el => el.style.display = 'block');
                        } else {
                            // Hide values completely
                            hiddenElements.forEach(el => el.style.display = 'block');
                            shownElements.forEach(el => el.style.display = 'none');
                        }
                    }

                    // Security functions
                    let hideTimeout;

                    function showSecurityOptions() {
                        const panel = document.getElementById('securityOptionsPanel');
                        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
                    }

                    function toggleApiValues() {
                        const checkbox = document.getElementById('showApiValues');
                        const hiddenElements = document.querySelectorAll('.api-value-hidden');
                        const shownElements = document.querySelectorAll('.api-value-shown');
                        const autoHide = document.getElementById('autoHideValues').checked;

                        // Clear existing timeout
                        if (hideTimeout) {
                            clearTimeout(hideTimeout);
                        }

                        if (checkbox.checked) {
                            // Show values (masked or full based on setting)
                            hiddenElements.forEach(el => el.style.display = 'none');
                            shownElements.forEach(el => el.style.display = 'block');

                            // Auto-hide if enabled
                            if (autoHide) {
                                hideTimeout = setTimeout(() => {
                                    checkbox.checked = false;
                                    hiddenElements.forEach(el => el.style.display = 'block');
                                    shownElements.forEach(el => el.style.display = 'none');

                                    showNotification('API values automatically hidden for security.', 'info');
                                }, 30000);
                            }
                        } else {
                            // Hide values completely
                            hiddenElements.forEach(el => el.style.display = 'block');
                            shownElements.forEach(el => el.style.display = 'none');
                        }
                    }

                    function showNotification(message, type = 'info') {
                        const alert = document.createElement('div');
                        alert.className = `alert alert-${type} alert-dismissible fade show mt-3`;
                        alert.innerHTML = `
                            <i class="fas fa-info-circle"></i>
                            ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;

                        const container = document.querySelector('.container');
                        const firstCard = container.querySelector('.card');
                        container.insertBefore(alert, firstCard);

                        // Auto-dismiss after 5 seconds
                        setTimeout(() => {
                            if (alert.parentNode) {
                                alert.remove();
                            }
                        }, 5000);
                    }

                    // Add warning when showing values
                    document.getElementById('showApiValues').addEventListener('change', function() {
                        if (this.checked) {
                            showNotification('⚠️ API values are now visible. They will be hidden automatically for security.', 'warning');
                        }
                    });
                </script>

                <!-- Existing APIs List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Configured APIs</h3>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showApiValues" onchange="toggleApiValues()">
                            <label class="form-check-label" for="showApiValues">
                                <i class="fas fa-eye"></i> Show API Values
                            </label>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($apis->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Value</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($apis as $api)
                                            <tr>
                                                <td>
                                                    <strong>{{ ucfirst(str_replace('_', ' ', $api->name)) }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ ucfirst($api->type) }}</span>
                                                </td>
                                                <td>
                                                    <div class="api-value-container">
                                                        <!-- Hidden by default -->
                                                        <div class="api-value-hidden">
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-lock"></i> Hidden
                                                            </span>
                                                            @if($api->hasValue())
                                                                <i class="fas fa-check-circle text-success ms-1" title="API value configured"></i>
                                                            @else
                                                                <i class="fas fa-exclamation-triangle text-warning ms-1" title="No API value"></i>
                                                            @endif
                                                        </div>
                                                        <!-- Shown when toggle is enabled -->
                                                        <div class="api-value-shown" style="display: none;">
                                                            <code class="text-truncate d-inline-block" style="max-width: 200px;">
                                                                {{ $api->masked_value }}
                                                            </code>
                                                            @if($api->hasValue())
                                                                <i class="fas fa-check-circle text-success ms-1" title="API value configured"></i>
                                                            @else
                                                                <i class="fas fa-exclamation-triangle text-warning ms-1" title="No API value"></i>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $api->description ?: 'No description' }}</td>
                                                <td>
                                                    @if($api->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>{{ $api->updated_at->diffForHumans() }}</td>
                                                <td>
                                                    <div class="gap-2">
                                                        <form action="{{ route('admin.apis.toggle', $api->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn-sm btn-outline-primary" style="border: 1.5px solid #0d6efd;">
                                                                {{ $api->is_active ? '⏸️' : '▶️' }}
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.apis.destroy', $api->id) }}" method="POST" 
                                                              class="d-inline" onsubmit="return confirm('Are you sure you want to delete this API?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn-sm btn-outline-primary" style="border: 1.5px solid #0d6efd;">🗑️</button>
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
                                <h4 class="text-muted">No APIs configured yet</h4>
                                <p class="text-muted">Add your first API configuration using the form above.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
