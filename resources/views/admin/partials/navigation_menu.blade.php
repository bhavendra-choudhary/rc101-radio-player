<div class="d-flex flex-column h-100">
    <div class="nav-header">Main Navigation</div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <a href="{{ route('admin.index') }}" class="nav-link {{ request()->is('admin') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
    </ul>

    <div class="nav-header">Content Management</div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <a href="{{ route('admin.ads.index') }}" class="nav-link {{ request()->is('admin/ads*') ? 'active' : '' }}">
                <i class="fas fa-ad"></i> Banner Ads
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.uploaded-news.index') }}" class="nav-link {{ request()->is('admin/uploaded-news*') || request()->is('admin/uploaded_news*') ? 'active' : '' }}">
                <i class="fas fa-newspaper"></i> News Articles
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.community-events.index') }}" class="nav-link {{ request()->is('admin/community-events*') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i> Community Events
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.contests.index') }}" class="nav-link {{ request()->is('admin/contests*') ? 'active' : '' }}">
                <i class="fas fa-trophy"></i> Contests & Giveaways
            </a>
        </li>
    </ul>

    <div class="nav-header">User Management</div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Listener Accounts
            </a>
        </li>
    </ul>

    <div class="nav-header">Site Configuration</div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}">
                <i class="fas fa-sliders-h"></i> Station Settings
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.apis') }}" class="nav-link {{ request()->is('admin/apis*') ? 'active' : '' }}">
                <i class="fas fa-plug"></i> Stream & API Settings
            </a>
        </li>
    </ul>

    <div class="mt-auto p-3 border-top border-secondary">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center">
                <i class="fas fa-sign-out-alt me-2"></i> Log Out
            </button>
        </form>
    </div>
</div>
