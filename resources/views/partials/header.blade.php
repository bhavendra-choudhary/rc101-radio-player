@php
    $headerLogo = \App\Models\SiteSetting::get('logo_path', 'Landscape_Logo.png');
    $headerLogoUrl = file_exists(public_path($headerLogo)) ? asset($headerLogo) : asset('Landscape_Logo.png');
    $headerBrandName = \App\Models\SiteSetting::get('app_name', \App\Models\Footer::getFooter()->brand_name ?? config('app.name'));
    $merchUrl = \App\Models\SiteSetting::get('affiliate_link_1_url', '#merchandise') ?: '#merchandise';
@endphp
<nav class="navbar navbar-expand-lg navbar-light" id="main-navbar">
    <div class="container-fluid px-3 px-lg-4 px-xl-5 header-nav-container">
        <a class="navbar-brand d-flex align-items-center" href="#top">
            <img src="{{ $headerLogoUrl }}" alt="{{ $headerBrandName }}" class="navbar-brand-logo" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
            <span class="fw-bold text-dark h4 mb-0 d-none">{{ $headerBrandName }}</span>
        </a>
        <button class="navbar-toggler mobile-hamburger-btn collapsed" type="button" aria-label="Toggle navigation">
            <div class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>

    <!-- Mobile App Side Drawer & Navigation -->
    <div class="collapse navbar-collapse" id="navbarNav">
        <!-- Mobile Drawer Header -->
        <div class="mobile-drawer-header d-lg-none">
            <!-- Mobile Drawer Header -->
            <div class="mobile-drawer-brand justify-content-end">
                <button type="button" class="mobile-drawer-close-btn mobile-drawer-close ms-auto" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Auth & Share Pills -->
            <div class="mobile-inline-actions">
                @auth
                    <div class="mobile-user-badge">
                        <i class="fas fa-user-circle"></i>
                        <span>{{ Str::limit(Auth::user()->name, 10) }}</span>
                    </div>
                    <form action="{{ route('user.logout') }}" method="POST" class="m-0" hx-boost="false" data-hx-boost="false">
                        @csrf
                        <button type="submit" class="mobile-action-btn mobile-action-logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                @else
                    <a class="mobile-action-btn mobile-action-signup" href="#" onclick="if(window.closeMobileDrawer) window.closeMobileDrawer(); openAuthModal('register'); return false;">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                    <a class="mobile-action-btn mobile-action-login" href="#" onclick="if(window.closeMobileDrawer) window.closeMobileDrawer(); openAuthModal('login'); return false;">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                @endauth
                
                <div class="dropdown">
                    <a class="mobile-action-btn mobile-action-share" href="#" id="mobileShareDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-share-alt"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 dropdown-menu-mobile-share" aria-labelledby="mobileShareDropdown">
                        <li><a class="dropdown-item py-2" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url('/')) }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook text-primary me-2"></i> Facebook</a></li>
                        <li><a class="dropdown-item py-2" href="https://twitter.com/intent/tweet?url={{ urlencode(url('/')) }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-twitter me-2 social-icon-twitter"></i> X (Twitter)</a></li>
                        <li><a class="dropdown-item py-2" href="https://api.whatsapp.com/send?text={{ urlencode(url('/')) }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp text-success me-2"></i> WhatsApp</a></li>
                        <li><a class="dropdown-item py-2" href="#" onclick="copyShareLink(event, '{{ url('/') }}', 'Link copied!')"><i class="fas fa-link text-secondary me-2"></i> Copy Link</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Navigation Links (Scrollable Container) -->
        <ul class="navbar-nav ms-auto align-items-center me-lg-2 me-xl-3">
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center px-2 px-xl-3" href="#top" data-nav-target="top">
                    <i class="fas fa-home me-1"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center px-2 px-xl-3" href="#pop-culture-news" data-nav-target="pop-culture-news">
                    <i class="fas fa-newspaper me-1"></i>
                    <span>News</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center px-2 px-xl-3" href="#concerts" data-nav-target="concerts">
                    <i class="fas fa-music me-1"></i>
                    <span>Concerts</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center px-2 px-xl-3" href="#events" data-nav-target="events">
                    <i class="fas fa-calendar-alt me-1"></i>
                    <span>Events</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center px-2 px-xl-3" href="#contest-section" data-nav-target="contest-section">
                    <i class="fas fa-trophy me-1"></i>
                    <span>Contest</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center px-2 px-xl-3" href="{{ route('home') }}#contact" data-nav-target="contact">
                    <i class="fas fa-envelope me-1"></i>
                    <span>Contact</span>
                </a>
            </li>


            
            <!-- Desktop Only Auth Group (Matches Reference Image) -->
            <li class="nav-item px-2 d-none d-lg-block" id="navAuthContainer">
            @auth
            <div class="dropdown d-inline-block">
                <a class="nav-link dropdown-toggle d-inline-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-1"></i>
                    <span>{{ Str::limit(Auth::user()->name, 12) }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 dropdown-menu-user-auth" aria-labelledby="userDropdown">
                    <li>
                        <form action="{{ route('user.logout') }}" method="POST" class="d-inline" hx-boost="false" data-hx-boost="false">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-danger fw-semibold">
                                <i class="fas fa-sign-out-alt me-2"></i> Log Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            @else
            <div class="header-auth-btns d-inline-flex align-items-center">
                <a class="btn-header-signup" href="#" onclick="openAuthModal('register'); return false;">
                    Sign Up
                </a>
                <a class="btn-header-login" href="#" onclick="openAuthModal('login'); return false;">
                    Login
                </a>
            </div>
            @endauth
            </li>

            <!-- Desktop Share Dropdown (Hidden to match reference header design) -->
            <li class="nav-item dropdown ms-2 d-none">
                <a class="nav-link dropdown-toggle no-hover d-inline-flex align-items-center" href="#" id="navbarShare" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Share">
                    <i class="fas fa-share-alt"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 dropdown-menu-desktop-share" aria-labelledby="navbarShare">
                    <li>
                        <a class="dropdown-item py-2"
                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url('/')) }}"
                            target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-facebook text-primary me-2 share-dropdown-icon"></i> Facebook
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2"
                            href="https://twitter.com/intent/tweet?url={{ urlencode(url('/')) }}"
                            target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-twitter me-2 share-dropdown-icon social-icon-twitter"></i> X (Twitter)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2"
                            href="https://api.whatsapp.com/send?text={{ urlencode(url('/')) }}"
                            target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-whatsapp text-success me-2 share-dropdown-icon"></i> WhatsApp
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="https://www.instagram.com/" target="_blank"
                            rel="noopener noreferrer"
                            onclick="shareToInstagram(event, '{{ url('/') }}')">
                            <i class="fab fa-instagram text-danger me-2 share-dropdown-icon"></i> Instagram
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="#"
                            onclick="copyShareLink(event, '{{ url('/') }}', 'Link copied to clipboard!')">
                            <i class="fas fa-link text-secondary me-2 share-dropdown-icon"></i> Copy Link
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
        </div>

       
    </div>
    </div>
</nav>

<!-- Backdrop Overlay for Mobile Drawer -->
<div class="mobile-drawer-backdrop" id="mobileDrawerBackdrop"></div>

<!-- Custom Share Toast -->
<div class="position-fixed bottom-0 end-0 p-3 share-toast-wrapper">
    <div id="shareToast" class="toast align-items-center text-white bg-success border-0" role="alert"
        aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="shareToastMessage">Link copied to clipboard!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
function initHeaderNavigation() {
    const hamburgerBtn = document.querySelector('.mobile-hamburger-btn');
    const navbarCollapse = document.getElementById('navbarNav');
    const backdrop = document.getElementById('mobileDrawerBackdrop');

    function openDrawer() {
        if (navbarCollapse) navbarCollapse.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
        if (hamburgerBtn) hamburgerBtn.classList.remove('collapsed');
        document.body.classList.add('mobile-menu-open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (navbarCollapse) navbarCollapse.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        if (hamburgerBtn) hamburgerBtn.classList.add('collapsed');
        document.body.classList.remove('mobile-menu-open');
        document.body.style.overflow = '';
    }

    // Expose close drawer helpers globally
    window.closeMobileDrawer = closeDrawer;
    window.openMobileDrawer = openDrawer;

    if (hamburgerBtn && !hamburgerBtn._hasNavClick) {
        hamburgerBtn._hasNavClick = true;
        hamburgerBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
    }

    if (backdrop && !backdrop._hasClick) {
        backdrop._hasClick = true;
        backdrop.addEventListener('click', closeDrawer);
    }

    // Auto-close drawer on nav links, close buttons, or mobile action buttons
    document.querySelectorAll('#navbarNav .nav-link:not(.dropdown-toggle), #navbarNav .mobile-drawer-close, #navbarNav a[data-bs-toggle="modal"], #navbarNav button[data-bs-toggle="modal"]').forEach(link => {
        if (!link._hasCloseDrawer) {
            link._hasCloseDrawer = true;
            link.addEventListener('click', function () {
                closeDrawer();
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', initHeaderNavigation);
if (document.readyState === 'interactive' || document.readyState === 'complete') {
    initHeaderNavigation();
}

document.addEventListener('DOMContentLoaded', function () {
    function setActiveNav(targetId) {
        if (!targetId) targetId = 'top';
        // Specifically target only the main section navigation links
        const sectionNavLinks = document.querySelectorAll('.navbar-nav > .nav-item:not(#navAuthContainer) > .nav-link:not(.dropdown-toggle)');
        
        // Ensure auth links and container never retain active state
        document.querySelectorAll('#navAuthContainer, #navAuthContainer .nav-link, .nav-auth-group, .nav-auth-group .nav-link').forEach(el => {
            el.classList.remove('active');
        });

        sectionNavLinks.forEach(link => {
            const href = link.getAttribute('href') || '';
            const dataTarget = link.getAttribute('data-nav-target') || '';
            let linkHash = dataTarget;
            if (!linkHash && href.includes('#')) {
                linkHash = href.substring(href.indexOf('#') + 1);
            }
            if (linkHash === targetId) {
                link.classList.add('active');
                if (link.parentElement && link.parentElement.classList.contains('nav-item')) {
                    link.parentElement.classList.add('active');
                }
            } else {
                link.classList.remove('active');
                if (link.parentElement && link.parentElement.classList.contains('nav-item')) {
                    link.parentElement.classList.remove('active');
                }
            }
        });
    }

    // ── Same-page navbar click handler ───────────────────────────────
    let _navClickLocked = false;
    let _navLockTimer = null;

    document.querySelectorAll('.navbar-nav > .nav-item:not(#navAuthContainer) > .nav-link:not(.dropdown-toggle), .navbar a[href*="#"]:not(#navAuthContainer a):not(.nav-auth-group a)').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href') || '';
            let targetId = this.getAttribute('data-nav-target') || '';
            if (!targetId && href.includes('#')) {
                targetId = href.substring(href.indexOf('#') + 1);
            }
            if (!targetId) return;

            const targetElement = (targetId === 'top') ? document.body : document.getElementById(targetId);
            if (targetElement || targetId === 'top') {
                e.preventDefault();

                // Prevent scrollspy from overriding during transition
                _navClickLocked = true;
                if (_navLockTimer) clearTimeout(_navLockTimer);

                // Set this item as the ONLY active nav item
                setActiveNav(targetId);

                // Defocus link so CSS :focus does not keep it artificially active/highlighted
                this.blur();

                // Scroll to target
                if (targetId === 'top') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    const navbar = document.querySelector('.navbar');
                    const navbarHeight = navbar ? navbar.offsetHeight : 70;
                    const offsetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - navbarHeight - 20;
                    window.scrollTo({ top: Math.max(0, offsetPosition), behavior: 'smooth' });
                }

                try {
                    history.pushState(null, null, '#' + targetId);
                } catch (err) {}

                // Keep scrollspy locked while scrolling to destination
                _navLockTimer = setTimeout(function () {
                    _navClickLocked = false;
                }, 1500);
            }
        });
    });

    if ('onscrollend' in window) {
        window.addEventListener('scrollend', function () {
            if (_navClickLocked) {
                setTimeout(function () {
                    _navClickLocked = false;
                }, 100);
            }
        });
    }

    // ── Dynamic Scrollspy to track active section while scrolling ─────
    let _scrollTicking = false;
    function onScrollSpy() {
        if (_navClickLocked) return;
        const scrollPos = window.pageYOffset || document.documentElement.scrollTop;
        const navbar = document.querySelector('.navbar');
        const navbarHeight = navbar ? navbar.offsetHeight : 70;

        // If user scrolled to the bottom of the page (within 100px of bottom), activate contact
        if ((window.innerHeight + scrollPos) >= (document.documentElement.scrollHeight - 100)) {
            setActiveNav('contact');
            return;
        }

        // Ordered sections on page from top to bottom
        const sections = [
            { id: 'pop-culture-news', el: document.getElementById('pop-culture-news') },
            { id: 'contest-section', el: document.getElementById('contest-section') },
            { id: 'concerts', el: document.getElementById('concerts') },
            { id: 'events', el: document.getElementById('events') },
            { id: 'contact', el: document.getElementById('contact') }
        ];

        // Bottom-up detection: find the lowest section that has reached the navbar threshold
        let currentId = 'top';
        for (let i = sections.length - 1; i >= 0; i--) {
            const sec = sections[i];
            if (sec.el) {
                const rect = sec.el.getBoundingClientRect();
                if (rect.top <= navbarHeight + 80) {
                    currentId = sec.id;
                    break;
                }
            }
        }

        setActiveNav(currentId);
    }

    window.addEventListener('scroll', function () {
        if (!_scrollTicking) {
            window.requestAnimationFrame(function () {
                onScrollSpy();
                _scrollTicking = false;
            });
            _scrollTicking = true;
        }
    }, { passive: true });

    // ── Hash-on-load handler ─────────────────────────────────────────
    if (window.location.hash) {
        const targetId = window.location.hash.substring(1);
        setActiveNav(targetId);
        let _scrollDone = false;
        let _fallbackTimer = null;

        function _doScroll() {
            const el = document.getElementById(targetId);
            if (!el) return;
            const navbar = document.querySelector('.navbar');
            const navbarHeight = navbar ? navbar.offsetHeight : 0;
            const offsetPosition =
                el.getBoundingClientRect().top +
                window.pageYOffset - navbarHeight - 40;
            window.scrollTo({ top: offsetPosition, behavior: 'instant' });
        }

        function _scrollToTarget() {
            if (_scrollDone) return;
            _scrollDone = true;
            clearTimeout(_fallbackTimer);
            _doScroll();

            let attempts = 0;
            const reAnchor = setInterval(function () {
                attempts++;
                _doScroll();
                if (attempts >= 6) clearInterval(reAnchor);
            }, 300);
        }

        window._asyncLoadersPending = 3;
        window.notifyAsyncLoadComplete = function () {
            window._asyncLoadersPending--;
            if (window._asyncLoadersPending <= 0) {
                window._asyncLoadersPending = 0;
                setTimeout(_scrollToTarget, 100);
            }
        };

        _fallbackTimer = setTimeout(_scrollToTarget, 5000);
    } else {
        setActiveNav('top');
        window.notifyAsyncLoadComplete = function () {};
    }
});

function showShareToast(message) {
    const toastMessage = document.getElementById('shareToastMessage');
    const toastEl = document.getElementById('shareToast');
    if (toastMessage && toastEl) {
        toastMessage.textContent = message || 'Link copied to clipboard!';
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }
}

function copyShareLink(event, url, message) {
    event.preventDefault();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url)
            .then(() => showShareToast(message))
            .catch(() => fallbackCopy(url, message));
    } else {
        fallbackCopy(url, message);
    }
}

function fallbackCopy(url, message) {
    const tempInput = document.createElement('input');
    tempInput.style = 'position: absolute; left: -1000px; top: -1000px';
    tempInput.value = url;
    document.body.appendChild(tempInput);
    tempInput.select();
    try {
        document.execCommand('copy');
        showShareToast(message);
    } catch (err) {}
    document.body.removeChild(tempInput);
}

function shareToInstagram(event, url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).catch(() => fallbackCopy(url, ''));
    } else {
        fallbackCopy(url, '');
    }
    showShareToast('Link copied! Paste it on Instagram — opening Instagram now…');
}
</script>