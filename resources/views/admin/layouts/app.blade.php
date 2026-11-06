<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            display: block;
            text-decoration: none;
        }
        .sidebar a:hover, .sidebar a.active {
            color: white;
            background-color: #495057;
            text-decoration: none;
        }
        .sidebar i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            padding: 20px;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar d-none d-md-block">
                <div class="mb-4 px-3">
                    <h4>Admin Panel</h4>
                </div>
                <nav class="nav flex-column">
                    <a href="{{ route('admin.contests.index') }}" class="nav-link {{ request()->is('admin/contests*') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i> Contests
                    </a>
                    <a href="{{ route('admin.ads.index') }}" class="nav-link {{ request()->is('admin/ads*') ? 'active' : '' }}">
                        <i class="fas fa-ad"></i> Manage Ads
                    </a>
                    <a href="{{ route('admin.contests.index') }}" class="nav-link {{ request()->is('admin/contests*') ? 'active' : '' }}">
                        <i class="fas fa-trophy"></i> Contests
                    </a>
                    <a href="{{ route('admin.apis') }}" class="nav-link {{ request()->is('admin/apis*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i> API Settings
                    </a>
                </nav>
            </div>

            <!-- Main content -->
            <div class="col-md-10 ms-sm-auto main-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        (function() {
            var keepAliveUrl = "{{ route('session.keepalive') }}";
            var loginUrl = "{{ route('admin.login') }}";
            setInterval(function() {
                fetch(keepAliveUrl, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 401 || r.status === 419 || r.redirected) window.location.replace(loginUrl + '?expired=1');
                    return r.json();
                })
                .then(function(d) {
                    if (d && d.csrf_token) {
                        var m = document.querySelector('meta[name="csrf-token"]');
                        if (m) m.setAttribute('content', d.csrf_token);
                        document.querySelectorAll('input[name="_token"]').forEach(function(i) { i.value = d.csrf_token; });
                    }
                }).catch(function() {});
            }, 300000);
        })();
    </script>
    @stack('scripts')
</body>
</html>
