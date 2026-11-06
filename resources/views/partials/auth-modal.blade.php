<!-- Global VIP Listener Auth Modal Popup (Sign Up / Log In) -->
<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered auth-modal-dialog">
        <div class="modal-content auth-modal-content">

            <!-- Modal Header Banner (Custom Dark/Red Gradient Header) -->
            <div class="auth-modal-header">
                <button type="button" class="auth-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
                <!-- Station Logo -->
                <div class="auth-logo-wrap">
                    <img src="{{ asset('Landscape_Logo.png') }}" alt="{{ config('app.name', 'Radio Station') }}" class="auth-modal-logo" onerror="this.classList.add('d-none'); document.getElementById('altAuthTitle').classList.remove('d-none');">
                    <h3 id="altAuthTitle" class="brand-font mb-0 text-white d-none">{{ config('app.name', 'Radio Station') }}</h3>
                </div>        
                <p class="auth-modal-subtitle">Unlock exclusive music. Request your song.</p>

                <!-- Navigation Tabs Switcher (Sign Up / Log In) -->
                <div class="auth-tabs-container">
                    <button type="button" id="tabRegisterBtn" class="auth-tab-btn active" onclick="switchAuthTab('register')">
                        <i class="fas fa-user-plus me-1"></i> <span>Sign Up</span>
                    </button>
                    <button type="button" id="tabLoginBtn" class="auth-tab-btn" onclick="switchAuthTab('login')">
                        <i class="fas fa-sign-in-alt me-1"></i> <span>Log In</span>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Clean White Light Theme Body) -->
            <div class="modal-body auth-modal-body">

                <!-- Alert Box -->
                <div id="modalAuthAlert" class="modal-auth-alert d-none">
                    <i id="modalAlertIcon" class="fas fa-info-circle"></i>
                    <span id="modalAlertText"></span>
                </div>

                <!-- ═════════════════ TAB 1: SIGN UP (REGISTER) ═════════════════ -->
                <div id="modalRegisterTab">
                    <!-- Step 1: Sign Up Details -->
                    <div id="regStep1">
                        <div class="modal-step-title">
                            Enter Your VIP Details
                        </div>

                        <form id="modalRegisterForm" method="POST" action="javascript:void(0);" onsubmit="handleModalRegisterSendOtp(event); return false;" novalidate>
                            @csrf

                            <!-- Name -->
                            <div class="modal-field-group">
                                <label for="modalRegName" class="modal-field-label">Your Name</label>
                                <div class="modal-input-wrap">
                                    <input type="text" id="modalRegName" class="modal-input" placeholder="Enter your name" required autocomplete="name">
                                    <i class="fas fa-user modal-field-icon"></i>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="modal-field-group">
                                <label for="modalRegPhone" class="modal-field-label">Phone Number</label>
                                <div class="modal-input-wrap">
                                    <input type="tel" id="modalRegPhone" class="modal-input" placeholder="Enter your Phone Number" required autocomplete="tel">
                                    <i class="fas fa-mobile-alt modal-field-icon"></i>
                                </div>
                            </div>

                            <!-- City with Auto-Detect -->
                            <div class="modal-field-group">
                                <label for="modalRegCity" class="modal-field-label">
                                    <span>Your City</span>
                                    <span id="modalCityBadge" class="modal-city-badge">
                                        <i class="fas fa-spinner fa-spin"></i> Detecting...
                                    </span>
                                </label>
                                <div class="modal-input-wrap">
                                    <input type="text" id="modalRegCity" class="modal-input" placeholder="Detecting city or type manually..." required autocomplete="address-level2">
                                    <i class="fas fa-map-marker-alt modal-field-icon"></i>
                                </div>
                            </div>

                            <button type="submit" id="btnModalRegSend" class="btn-modal-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send Verification Code
                            </button>
                        </form>
                    </div>

                    <!-- Step 2: Sign Up OTP Verification -->
                    <div id="regStep2" class="d-none">
                        <div class="modal-step-title">
                            Enter 6-Digit Verification Code
                        </div>

                        <div class="modal-info-box">
                            <i class="fas fa-sms"></i>
                            <div>
                                <small>Code sent to</small>
                                <strong id="regSentPhoneDisplay"></strong>
                            </div>
                        </div>

                        <form id="modalRegVerifyForm" method="POST" action="javascript:void(0);" onsubmit="handleModalRegisterVerifyOtp(event); return false;" novalidate>
                            @csrf
                            <div class="modal-otp-grid">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box reg-otp-box" data-index="0">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box reg-otp-box" data-index="1">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box reg-otp-box" data-index="2">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box reg-otp-box" data-index="3">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box reg-otp-box" data-index="4">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box reg-otp-box" data-index="5">
                            </div>

                            <button type="submit" id="btnModalRegVerify" class="btn-modal-primary">
                                <i class="fas fa-check-circle me-1"></i> Verify &amp; Complete Sign Up
                            </button>
                        </form>

                        <div class="text-center mt-3 small text-muted">
                            Didn't receive code?&nbsp;
                            <button type="button" class="btn-modal-link" onclick="handleModalRegisterSendOtp(event)">Resend Code</button>
                        </div>
                        <div class="text-center mt-2">
                            <button type="button" class="btn-modal-link text-secondary" onclick="showModalRegStep1()">
                                <i class="fas fa-arrow-left me-1"></i> Edit Details
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ═════════════════ TAB 2: LOG IN ═════════════════ -->
                <div id="modalLoginTab" class="d-none">
                    <!-- Step 1: Login Phone -->
                    <div id="loginStep1">
                        <div class="modal-step-title">
                            Enter Your Registered Phone Number
                        </div>

                        <form id="modalLoginForm" method="POST" action="javascript:void(0);" onsubmit="handleModalLoginSendOtp(event); return false;" novalidate>
                            @csrf
                            <div class="modal-field-group">
                                <label for="modalLoginPhone" class="modal-field-label">Phone Number</label>
                                <div class="modal-input-wrap">
                                    <input type="tel" id="modalLoginPhone" class="modal-input" placeholder="Enter your Phone Number" required autocomplete="tel">
                                    <i class="fas fa-mobile-alt modal-field-icon"></i>
                                </div>
                            </div>

                            <button type="submit" id="btnModalLoginSend" class="btn-modal-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send Verification Code
                            </button>
                        </form>
                    </div>

                    <!-- Step 2: Login OTP Verification -->
                    <div id="loginStep2" class="d-none">
                        <div class="modal-step-title">
                            Enter 6-Digit Verification Code
                        </div>

                        <div class="modal-info-box">
                            <i class="fas fa-sms"></i>
                            <div>
                                <small>Code sent to</small>
                                <strong id="loginSentPhoneDisplay"></strong>
                            </div>
                        </div>

                        <form id="modalLoginVerifyForm" method="POST" action="javascript:void(0);" onsubmit="handleModalLoginVerifyOtp(event); return false;" novalidate>
                            @csrf
                            <div class="modal-otp-grid">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box login-otp-box" data-index="0">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box login-otp-box" data-index="1">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box login-otp-box" data-index="2">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box login-otp-box" data-index="3">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box login-otp-box" data-index="4">
                                <input type="text" inputmode="numeric" maxlength="1" class="modal-otp-box login-otp-box" data-index="5">
                            </div>

                            <button type="submit" id="btnModalLoginVerify" class="btn-modal-primary">
                                <i class="fas fa-check-circle me-1"></i> Verify &amp; Log In
                            </button>
                        </form>

                        <div class="text-center mt-3 small text-muted">
                            Didn't receive code?&nbsp;
                            <button type="button" class="btn-modal-link" onclick="handleModalLoginSendOtp(event)">Resend Code</button>
                        </div>
                        <div class="text-center mt-2">
                            <button type="button" class="btn-modal-link text-secondary" onclick="showModalLoginStep1()">
                                <i class="fas fa-arrow-left me-1"></i> Change Number
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.location.search && window.location.search.indexOf('_token=') !== -1) {
        try {
            var cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('_token');
            var newUrlStr = cleanUrl.pathname + (cleanUrl.search ? cleanUrl.search : '') + cleanUrl.hash;
            window.history.replaceState({}, document.title, newUrlStr);
        } catch(err) {}
    }

    detectModalCity();
    setupOtpInputHandling('.reg-otp-box');
    setupOtpInputHandling('.login-otp-box');

    // Auto-open modal if redirected with ?auth=register or ?auth=login
    try {
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('auth')) {
            var targetTab = urlParams.get('auth');
            openAuthModal(targetTab === 'login' ? 'login' : 'register');
        }
    } catch(e) {}
});

/* ── Switch Tab Between Register & Login ── */
function switchAuthTab(tab) {
    var regTab = document.getElementById('modalRegisterTab');
    var loginTab = document.getElementById('modalLoginTab');
    var regBtn = document.getElementById('tabRegisterBtn');
    var loginBtn = document.getElementById('tabLoginBtn');
    hideModalAlert();

    if (tab === 'register') {
        if (regTab) regTab.classList.remove('d-none');
        if (loginTab) loginTab.classList.add('d-none');
        if (regBtn) regBtn.classList.add('active');
        if (loginBtn) loginBtn.classList.remove('active');
    } else {
        if (loginTab) loginTab.classList.remove('d-none');
        if (regTab) regTab.classList.add('d-none');
        if (loginBtn) loginBtn.classList.add('active');
        if (regBtn) regBtn.classList.remove('active');
    }
}
window.switchAuthTab = switchAuthTab;

/* ── Open Modal Helper ── */
function openAuthModal(tab) {
    if (!tab) tab = 'register';
    if (typeof window.closeMobileDrawer === 'function') {
        window.closeMobileDrawer();
    } else {
        var navbarCollapse = document.getElementById('navbarNav');
        var backdrop = document.getElementById('mobileDrawerBackdrop');
        var hamburgerBtn = document.querySelector('.mobile-hamburger-btn');
        if (navbarCollapse) navbarCollapse.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        if (hamburgerBtn) hamburgerBtn.classList.add('collapsed');
        document.body.classList.remove('mobile-menu-open');
        document.body.style.overflow = '';
    }
    switchAuthTab(tab);
    var modalEl = document.getElementById('authModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = bootstrap.Modal.getInstance(modalEl);
        if (!bsModal) {
            bsModal = new bootstrap.Modal(modalEl);
        }
        bsModal.show();
    }
}
window.openAuthModal = openAuthModal;

document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('authModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function() {
            if (typeof window.closeMobileDrawer === 'function') {
                window.closeMobileDrawer();
            }
        });
    }
});

/* ── Auto Detect City ── */
function detectModalCity() {
    var cityInput = document.getElementById('modalRegCity');
    var badge = document.getElementById('modalCityBadge');
    if (!cityInput || !badge) return;

    function getAuthCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
        return null;
    }

    var savedCity = getAuthCookie('user_location_city') || localStorage.getItem('user_location_city');
    if (savedCity) {
        cityInput.value = savedCity;
        badge.className = 'modal-city-badge success';
        badge.innerHTML = '<i class="fas fa-check-circle"></i> Location Detected';
        return;
    }

    fallbackModalIpCity();
}

function fallbackModalIpCity() {
    var cityInput = document.getElementById('modalRegCity');
    var badge = document.getElementById('modalCityBadge');
    if (typeof fetch === 'function') {
        fetch('https://ipapi.co/json/')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.city && cityInput && !cityInput.value) {
                    cityInput.value = data.city;
                    if (badge) {
                        badge.className = 'modal-city-badge success';
                        badge.innerHTML = '<i class="fas fa-check-circle"></i> Location Detected';
                    }
                }
            })
            .catch(function() {
                if (badge) {
                    badge.className = 'modal-city-badge';
                    badge.innerHTML = '<i class="fas fa-edit"></i> Type city';
                }
            });
    }
}

/* ── OTP Boxes Auto Jump ── */
function setupOtpInputHandling(selector) {
    var boxes = document.querySelectorAll(selector);
    if (!boxes || !boxes.length) return;
    Array.prototype.forEach.call(boxes, function(box, index) {
        box.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
            if (e.target.value.length === 1 && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
        });
        box.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !box.value && index > 0) {
                boxes[index - 1].focus();
            }
        });
        box.addEventListener('paste', function(e) {
            e.preventDefault();
            var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').trim();
            if (pasted.length >= 6) {
                pasted.slice(0, 6).split('').forEach(function(char, i) {
                    if (boxes[i]) boxes[i].value = char;
                });
                boxes[5].focus();
            }
        });
    });
}

function getOtpFromBoxes(selector) {
    var code = '';
    var boxes = document.querySelectorAll(selector);
    for (var i = 0; i < boxes.length; i++) {
        code += boxes[i].value;
    }
    return code;
}

/* ── Alerts ── */
function showModalAlert(type, message) {
    var box = document.getElementById('modalAuthAlert');
    var icon = document.getElementById('modalAlertIcon');
    var text = document.getElementById('modalAlertText');
    if (box) {
        box.className = 'modal-auth-alert modal-auth-alert-' + (type === 'error' ? 'error' : 'success');
    }
    if (icon) {
        icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    }
    if (text) {
        text.textContent = message;
    }
}

function hideModalAlert() {
    var box = document.getElementById('modalAuthAlert');
    if (box) box.className = 'modal-auth-alert d-none';
}

/* ── Register Step 1: Send OTP ── */
function handleModalRegisterSendOtp(e) {
    if (e && e.preventDefault) e.preventDefault();
    hideModalAlert();

    var name = document.getElementById('modalRegName').value.trim();
    var phone = document.getElementById('modalRegPhone').value.trim();
    var city = document.getElementById('modalRegCity').value.trim();

    if (!name) { showModalAlert('error', 'Please enter your name.'); return; }
    if (!phone) { showModalAlert('error', 'Please enter your phone number.'); return; }
    if (!city) { showModalAlert('error', 'Please enter or confirm your city.'); return; }

    var btn = document.getElementById('btnModalRegSend');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> <span>Sending...</span>';

    var csrfToken = '';
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (tokenMeta) csrfToken = tokenMeta.content;

    fetch("{{ route('user.register.send') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ name: name, phone: phone, city: city })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('regSentPhoneDisplay').textContent = phone;
            var msg = data.message;
            if (data.debug_otp) {
                msg = 'Code sent! (Dev code: ' + data.debug_otp + ')';
                var digits = String(data.debug_otp).split('');
                var boxes = document.querySelectorAll('.reg-otp-box');
                for (var i = 0; i < digits.length; i++) {
                    if (boxes[i]) boxes[i].value = digits[i];
                }
            }
            showModalAlert('success', msg);
            document.getElementById('regStep1').classList.add('d-none');
            document.getElementById('regStep2').classList.remove('d-none');
            var firstOtp = document.querySelectorAll('.reg-otp-box')[0];
            if (firstOtp) firstOtp.focus();
        } else {
            showModalAlert('error', data.message || 'Could not send verification code.');
        }
    })
    .catch(function() {
        showModalAlert('error', 'Network error. Please try again.');
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Verification Code';
    });
}
window.handleModalRegisterSendOtp = handleModalRegisterSendOtp;

/* ── Register Step 2: Verify OTP ── */
function handleModalRegisterVerifyOtp(e) {
    if (e && e.preventDefault) e.preventDefault();
    hideModalAlert();

    var phone = document.getElementById('modalRegPhone').value.trim();
    var city = document.getElementById('modalRegCity').value.trim();
    var name = document.getElementById('modalRegName').value.trim();
    var otp = getOtpFromBoxes('.reg-otp-box');

    if (otp.length !== 6) {
        showModalAlert('error', 'Please enter all 6 digits.');
        return;
    }

    var btn = document.getElementById('btnModalRegVerify');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> <span>Verifying...</span>';

    var csrfToken = '';
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (tokenMeta) csrfToken = tokenMeta.content;

    fetch("{{ route('user.otp.verify') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ identifier: phone, otp: otp, city: city, name: name })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showModalAlert('success', 'Registration complete! Welcome to ' + "{{ config('app.name', 'the station') }}");
            setTimeout(function() {
                var modalEl = document.getElementById('authModal');
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                }
                var navContainer = document.getElementById('navAuthContainer');
                if (navContainer && data.user) {
                    var displayName = (data.user.name || 'VIP Listener').slice(0, 12);
                    navContainer.innerHTML = '<div class="dropdown d-inline-block"><a class="nav-link nav-user-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user-circle me-1"></i> ' + displayName + '</a><ul class="dropdown-menu dropdown-menu-end shadow border-0 dropdown-menu-user-auth" aria-labelledby="userDropdown"><li><form action="{{ route("user.logout") }}" method="POST" class="d-inline">@csrf<button type="submit" class="dropdown-item py-2 text-danger fw-semibold"><i class="fas fa-sign-out-alt me-2"></i> Log Out</button></form></li></ul></div>';
                }
                if (typeof showShareToast === 'function') {
                    showShareToast('Welcome! Logged in as ' + (data.user ? data.user.name : 'VIP Listener'));
                }
                window.dispatchEvent(new CustomEvent('userLoggedIn', { detail: data.user }));
            }, 800);
        } else {
            showModalAlert('error', data.message || 'Invalid verification code.');
        }
    })
    .catch(function() {
        showModalAlert('error', 'Verification failed. Please try again.');
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Verify &amp; Complete Sign Up';
    });
}
window.handleModalRegisterVerifyOtp = handleModalRegisterVerifyOtp;

/* ── Login Step 1: Send OTP ── */
function handleModalLoginSendOtp(e) {
    if (e && e.preventDefault) e.preventDefault();
    hideModalAlert();

    var identifier = document.getElementById('modalLoginPhone').value.trim();
    if (!identifier) {
        showModalAlert('error', 'Please enter your phone number.');
        return;
    }

    var btn = document.getElementById('btnModalLoginSend');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> <span>Sending...</span>';

    var csrfToken = '';
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (tokenMeta) csrfToken = tokenMeta.content;

    fetch("{{ route('user.otp.send') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ identifier: identifier })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('loginSentPhoneDisplay').textContent = identifier;
            var msg = data.message;
            if (data.debug_otp) {
                msg = 'Code sent! (Dev code: ' + data.debug_otp + ')';
                var digits = String(data.debug_otp).split('');
                var boxes = document.querySelectorAll('.login-otp-box');
                for (var i = 0; i < digits.length; i++) {
                    if (boxes[i]) boxes[i].value = digits[i];
                }
            }
            showModalAlert('success', msg);
            document.getElementById('loginStep1').classList.add('d-none');
            document.getElementById('loginStep2').classList.remove('d-none');
            var firstOtp = document.querySelectorAll('.login-otp-box')[0];
            if (firstOtp) firstOtp.focus();
        } else {
            showModalAlert('error', data.message || 'Could not send verification code.');
        }
    })
    .catch(function() {
        showModalAlert('error', 'Network error. Please try again.');
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Verification Code';
    });
}
window.handleModalLoginSendOtp = handleModalLoginSendOtp;

/* ── Login Step 2: Verify OTP ── */
function handleModalLoginVerifyOtp(e) {
    if (e && e.preventDefault) e.preventDefault();
    hideModalAlert();

    var identifier = document.getElementById('modalLoginPhone').value.trim();
    var otp = getOtpFromBoxes('.login-otp-box');

    if (otp.length !== 6) {
        showModalAlert('error', 'Please enter all 6 digits.');
        return;
    }

    var btn = document.getElementById('btnModalLoginVerify');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> <span>Verifying...</span>';

    var csrfToken = '';
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (tokenMeta) csrfToken = tokenMeta.content;

    fetch("{{ route('user.otp.verify') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ identifier: identifier, otp: otp })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showModalAlert('success', 'Logged in successfully!');
            setTimeout(function() {
                var modalEl = document.getElementById('authModal');
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                }
                var navContainer = document.getElementById('navAuthContainer');
                if (navContainer && data.user) {
                    var displayName = (data.user.name || 'VIP Listener').slice(0, 12);
                    navContainer.innerHTML = '<div class="dropdown d-inline-block"><a class="nav-link nav-user-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user-circle me-1"></i> ' + displayName + '</a><ul class="dropdown-menu dropdown-menu-end shadow border-0 dropdown-menu-user-auth" aria-labelledby="userDropdown"><li><form action="{{ route("user.logout") }}" method="POST" class="d-inline">@csrf<button type="submit" class="dropdown-item py-2 text-danger fw-semibold"><i class="fas fa-sign-out-alt me-2"></i> Log Out</button></form></li></ul></div>';
                }
                if (typeof showShareToast === 'function') {
                    showShareToast('Welcome back, ' + (data.user ? data.user.name : 'VIP Listener') + '!');
                }
                window.dispatchEvent(new CustomEvent('userLoggedIn', { detail: data.user }));
            }, 800);
        } else {
            showModalAlert('error', data.message || 'Invalid verification code.');
        }
    })
    .catch(function() {
        showModalAlert('error', 'Verification failed. Please try again.');
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Verify &amp; Log In';
    });
}
window.handleModalLoginVerifyOtp = handleModalLoginVerifyOtp;

function showModalRegStep1() {
    var s1 = document.getElementById('regStep1');
    var s2 = document.getElementById('regStep2');
    if (s1) s1.classList.remove('d-none');
    if (s2) s2.classList.add('d-none');
    hideModalAlert();
}
window.showModalRegStep1 = showModalRegStep1;

function showModalLoginStep1() {
    var s1 = document.getElementById('loginStep1');
    var s2 = document.getElementById('loginStep2');
    if (s1) s1.classList.remove('d-none');
    if (s2) s2.classList.add('d-none');
    hideModalAlert();
}
window.showModalLoginStep1 = showModalLoginStep1;
</script>
