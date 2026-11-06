@php
    $isAdmin = request()->is('admin*') || str_contains(url()->previous(), '/admin') || str_contains(request()->headers->get('referer', ''), '/admin');
    $redirectUrl = $isAdmin ? route('admin.login') : (Route::has('user.login') ? route('user.login') : url('/login'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="0;url={{ $redirectUrl }}?expired=1">
    <title>Session Expired - Redirecting to Login...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Immediate JavaScript redirect to prevent static 419 error display
        window.location.replace("{{ $redirectUrl }}?expired=1");
    </script>
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .redirect-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 32px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255, 193, 7, 0.2);
            border-top-color: #ffc107;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="redirect-card">
        <div class="spinner"></div>
        <h4 class="mb-2">Session Expired</h4>
        <p class="text-muted mb-4">Your session has expired. Redirecting you to the login page...</p>
        <a href="{{ $redirectUrl }}?expired=1" class="btn btn-warning w-100 fw-bold">
            Continue to Login
        </a>
    </div>
</body>
</html>
