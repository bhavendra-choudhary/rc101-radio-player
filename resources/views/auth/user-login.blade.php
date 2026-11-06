@extends('layouts.app')

@section('title', 'Listener Login | ' . config('app.name', 'Radio Station'))



@section('content')
<div class="auth-page-container">
    <div class="auth-card">

        <!-- Card Header -->
        <div class="auth-card-header">
            <div class="auth-header-icon">
                <i class="fas fa-user-lock"></i>
            </div>
            <h2 class="brand-font">{{ config('app.name', 'Radio Station') }}</h2>
            <p>Listener Account Sign-In</p>
        </div>

        <!-- Card Body -->
        <div class="auth-card-body">

            <!-- Alert Notification -->
            <div id="alertBox" class="auth-alert d-none">
                <i id="alertIcon" class="fas fa-info-circle"></i>
                <span id="alertText"></span>
            </div>

            <!-- STEP 1: Phone / Identifier Input -->
            <div id="step1">
                <div class="auth-step-title">
                    Enter Your Registered Phone Number
                </div>

                <form id="sendOtpForm" method="POST" action="javascript:void(0);" onsubmit="handleSendOtp(event); return false;" novalidate>
                    @csrf
                    <div class="auth-field-wrap">
                        <label for="identifier" class="auth-field-label">Phone Number</label>
                        <div class="auth-input-relative">
                            <input type="tel"
                                   id="identifier"
                                   name="identifier"
                                   class="auth-input"
                                   placeholder="Enter your phone number"
                                   required
                                   autocomplete="tel"
                                   autofocus>
                            <i class="fas fa-mobile-alt auth-field-icon"></i>
                        </div>
                    </div>

                    <button type="submit" id="btnSendOtp" class="btn-auth-submit">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send Verification Code</span>
                    </button>
                </form>
            </div>

            <!-- STEP 2: OTP Verification -->
            <div id="step2" class="d-none">
                <div class="auth-step-title">
                    Enter Your 6-Digit Code
                </div>

                <div class="info-sent-box">
                    <i class="fas fa-sms"></i>
                    <div>
                        <small>Verification code sent to</small>
                        <strong id="sentTargetDisplay"></strong>
                    </div>
                </div>

                <form id="verifyOtpForm" method="POST" action="javascript:void(0);" onsubmit="handleVerifyOtp(event); return false;" novalidate>
                    @csrf
                    <input type="hidden" id="verifyIdentifier" name="identifier">

                    <div class="otp-inputs-flex">
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="0" autofocus>
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="1">
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="2">
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="3">
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="4">
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="5">
                    </div>

                    <button type="submit" id="btnVerifyOtp" class="btn-auth-submit">
                        <i class="fas fa-check-circle"></i>
                        <span>Verify Code &amp; Log In</span>
                    </button>
                </form>

                <div class="text-center mt-3 text-muted small">
                    Didn't receive code?&nbsp;
                    <button type="button" id="btnResend" class="btn-link-action" onclick="resendOtp()">Resend Code</button>
                    <span id="timerText" class="d-none text-muted font-monospace ms-1"></span>
                </div>

                <div class="text-center mt-3 pt-2">
                    <button type="button" class="btn-link-action" style="color:#64748b !important;" onclick="showStep1()">
                        <i class="fas fa-arrow-left me-1"></i> Change Number
                    </button>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="auth-card-footer">
                Need an account?
                <a href="{{ route('user.register') }}">Sign Up Here</a>
                &bull;
                <a href="{{ route('home') }}">Back to Station</a>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.location.search && window.location.search.includes('_token=')) {
        try {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('_token');
            const newUrlStr = cleanUrl.pathname + (cleanUrl.search ? cleanUrl.search : '') + cleanUrl.hash;
            window.history.replaceState({}, document.title, newUrlStr);
        } catch(err) {}
    }
});

let countdownInterval;

/* ── OTP Field Jumping ── */
const otpBoxes = document.querySelectorAll('.otp-box');
otpBoxes.forEach((box, index) => {
    box.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value.length === 1 && index < otpBoxes.length - 1) {
            otpBoxes[index + 1].focus();
        }
        e.target.classList.toggle('filled', e.target.value.length > 0);
        updateFullOtp();
    });

    box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && index > 0) {
            otpBoxes[index - 1].focus();
            otpBoxes[index - 1].classList.remove('filled');
        }
    });

    box.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').trim();
        if (pasted.length >= 6) {
            pasted.slice(0, 6).split('').forEach((char, i) => {
                if (otpBoxes[i]) {
                    otpBoxes[i].value = char;
                    otpBoxes[i].classList.add('filled');
                }
            });
            otpBoxes[5].focus();
            updateFullOtp();
        }
    });
});

function updateFullOtp() {
    let code = '';
    otpBoxes.forEach(b => code += b.value);
    return code;
}

/* ── Alert Utilities ── */
function showAlert(type, message) {
    const box = document.getElementById('alertBox');
    const icon = document.getElementById('alertIcon');
    const text = document.getElementById('alertText');

    box.className = `auth-alert auth-alert-${type === 'error' ? 'error' : 'success'}`;
    icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    text.textContent = message;
}

function hideAlert() {
    document.getElementById('alertBox').className = 'auth-alert d-none';
}

/* ── Step 1: Send OTP ── */
async function handleSendOtp(e) {
    e.preventDefault();
    hideAlert();

    const identifier = document.getElementById('identifier').value.trim();
    if (!identifier) {
        showAlert('error', 'Please enter your registered phone number.');
        return;
    }

    const btn = document.getElementById('btnSendOtp');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> <span>Sending Code...</span>`;

    try {
        const res = await fetch("{{ route('user.otp.send') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ identifier })
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('verifyIdentifier').value = identifier;
            document.getElementById('sentTargetDisplay').textContent = identifier;

            let msg = data.message;
            if (data.debug_otp) {
                msg = `Verification code sent! (Dev code: ${data.debug_otp})`;
                String(data.debug_otp).split('').forEach((c, i) => {
                    if (otpBoxes[i]) {
                        otpBoxes[i].value = c;
                        otpBoxes[i].classList.add('filled');
                    }
                });
            }
            showAlert('success', msg);
            showStep2();
            startResendTimer(60);
        } else {
            showAlert('error', data.message || 'Could not send verification code.');
        }
    } catch (err) {
        showAlert('error', 'Network error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-paper-plane"></i> <span>Send Verification Code</span>`;
    }
}

/* ── Step 2: Verify OTP & Log In ── */
async function handleVerifyOtp(e) {
    e.preventDefault();
    hideAlert();

    const identifier = document.getElementById('verifyIdentifier').value || document.getElementById('identifier').value;
    const otp = updateFullOtp();

    if (otp.length !== 6) {
        showAlert('error', 'Please enter all 6 digits of your verification code.');
        return;
    }

    const btn = document.getElementById('btnVerifyOtp');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> <span>Verifying...</span>`;

    try {
        const res = await fetch("{{ route('user.otp.verify') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ identifier, otp })
        });
        const data = await res.json();

        if (data.success) {
            showAlert('success', 'Logged in successfully! Redirecting...');
            setTimeout(() => {
                window.location.href = data.redirect_url || "{{ route('home') }}";
            }, 800);
        } else {
            showAlert('error', data.message || 'Invalid verification code.');
        }
    } catch (err) {
        showAlert('error', 'Verification failed. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-check-circle"></i> <span>Verify Code &amp; Log In</span>`;
    }
}

function resendOtp() {
    document.getElementById('sendOtpForm').dispatchEvent(new Event('submit'));
}

function startResendTimer(seconds) {
    const btnResend = document.getElementById('btnResend');
    const timerText = document.getElementById('timerText');
    btnResend.classList.add('d-none');
    timerText.classList.remove('d-none');

    clearInterval(countdownInterval);
    let remaining = seconds;
    timerText.textContent = `${remaining}s`;

    countdownInterval = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
            clearInterval(countdownInterval);
            timerText.classList.add('d-none');
            btnResend.classList.remove('d-none');
        } else {
            timerText.textContent = `${remaining}s`;
        }
    }, 1000);
}

function showStep1() {
    document.getElementById('step1').classList.remove('d-none');
    document.getElementById('step2').classList.add('d-none');
    hideAlert();
}

function showStep2() {
    document.getElementById('step1').classList.add('d-none');
    document.getElementById('step2').classList.remove('d-none');
    if (otpBoxes[0]) otpBoxes[0].focus();
}
</script>
@endpush
@endsection
