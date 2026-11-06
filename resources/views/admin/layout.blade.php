<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>@yield('title', config('app.name') . ' - Admin Control Panel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --sidebar-active: #475569;
            --primary-accent: #667eea;
        }

        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: #f8fafc;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .nav-header {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #94a3b8;
            padding: 1.25rem 1.5rem 0.5rem 1.5rem;
            letter-spacing: 1px;
        }

        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover {
            color: #ffffff;
            background: var(--sidebar-hover);
        }

        .sidebar .nav-link.active {
            color: #ffffff;
            background: var(--sidebar-active);
            border-left-color: var(--primary-accent);
        }

        .sidebar .nav-link i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }

        .main-content {
            padding: 2rem;
            min-height: 100vh;
        }

        /* Responsive Layout Adjustments */
        @media (max-width: 991.98px) {
            .main-content {
                padding: 1rem 0.75rem;
            }
        }

        .offcanvas-admin {
            background: var(--sidebar-bg);
            color: white;
            width: 280px;
        }

        /* Touch Control Friendly Inputs */
        .btn, .form-control, .form-select, .nav-link {
            touch-action: manipulation;
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Mobile & Tablet Top Navigation Bar (< 992px) -->
    <div class="d-lg-none bg-dark text-white p-3 d-flex justify-content-between align-items-center sticky-top shadow-sm" style="z-index: 1040;">
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-light me-3 border-0 px-2 py-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileSidebar" aria-controls="adminMobileSidebar" aria-label="Open Navigation Menu">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            <span class="fw-bold fs-5 mb-0"><i class="fas fa-broadcast-tower text-primary me-2"></i>{{ config('app.name') }}</span>
        </div>
        <span class="badge bg-primary">Admin</span>
    </div>

    <!-- Mobile & Tablet Slide-out Offcanvas Navigation Drawer -->
    <div class="offcanvas offcanvas-start offcanvas-admin d-lg-none" tabindex="-1" id="adminMobileSidebar" aria-labelledby="adminMobileSidebarLabel">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title fw-bold" id="adminMobileSidebarLabel">
                <i class="fas fa-broadcast-tower me-2 text-primary"></i>{{ config('app.name') }} Admin
            </h5>
            <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close Navigation"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('admin.partials.navigation_menu')
        </div>
    </div>

    <div class="container-fluid p-0 p-lg-3">
        <div class="row g-0">
            <!-- Desktop Sidebar Navigation (>= 992px) -->
            <div class="col-lg-2 p-0 sidebar d-none d-lg-block rounded-3 overflow-hidden shadow-sm">
                <div class="sidebar-brand d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-broadcast-tower me-2 text-primary"></i>{{ config('app.name') }}</span>
                </div>
                @include('admin.partials.navigation_menu')
            </div>

            <!-- Main Content Area -->
            <div class="col-12 col-lg-10 main-content">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Session Keepalive & Auto-Refresh Script (Keeps session alive forever, auto-reverts to login on drop) -->
    <script>
        (function() {
            var keepAliveUrl = "{{ route('session.keepalive') }}";
            var loginUrl = "{{ route('admin.login') }}";
            var lastPing = Date.now();

            function refreshSessionAndTokens() {
                fetch(keepAliveUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    if (res.status === 401 || res.status === 419 || res.redirected) {
                        window.location.replace(loginUrl + '?expired=1');
                        return null;
                    }
                    return res.json();
                })
                .then(function(data) {
                    if (!data) return;
                    if (data.authenticated === false) {
                        window.location.replace(loginUrl + '?expired=1');
                        return;
                    }
                    if (data.csrf_token) {
                        var meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.setAttribute('content', data.csrf_token);

                        var tokenInputs = document.querySelectorAll('input[name="_token"]');
                        tokenInputs.forEach(function(inp) {
                            inp.value = data.csrf_token;
                        });
                    }
                    lastPing = Date.now();
                })
                .catch(function(err) {
                    console.warn('[AdminSession] Keepalive heartbeat ping warning:', err);
                });
            }

            // Periodic heartbeat ping every 5 minutes (300,000ms)
            setInterval(refreshSessionAndTokens, 300000);

            // Immediate heartbeat on tab focus / visibility return
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    if (Date.now() - lastPing > 60000) {
                        refreshSessionAndTokens();
                    }
                }
            });
            window.addEventListener('focus', function() {
                if (Date.now() - lastPing > 60000) {
                    refreshSessionAndTokens();
                }
            });

            // Global fetch interceptor to redirect to login on any 419 Page Expired status
            var originalFetch = window.fetch;
            window.fetch = function() {
                return originalFetch.apply(this, arguments).then(function(response) {
                    if (response.status === 419 || (response.status === 401 && window.location.pathname.startsWith('/admin'))) {
                        window.location.replace(loginUrl + '?expired=1');
                    }
                    return response;
                });
            };
        })();
    </script>
    @stack('scripts')
</body>
</html>
