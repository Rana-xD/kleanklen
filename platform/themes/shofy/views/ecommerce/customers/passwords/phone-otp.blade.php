@php
    Theme::set('breadcrumbHeight', 100);
    Theme::set('pageTitle', __('Enter Verification Code'));
    SeoHelper::setTitle(__('Enter Verification Code'));
    Theme::breadcrumb()
        ->add(__('Reset Password'), route('customer.password.phone'))
        ->add(__('Verify Code'));
@endphp

<section class="tp-login-area pb-140 p-relative z-index-1 fix">
    <div class="tp-login-shape">
        <img class="tp-login-shape-1" src="{{ Theme::asset()->url('images/login/login-shape-1.png') }}" alt="">
        <img class="tp-login-shape-2" src="{{ Theme::asset()->url('images/login/login-shape-2.png') }}" alt="">
        <img class="tp-login-shape-3" src="{{ Theme::asset()->url('images/login/login-shape-3.png') }}" alt="">
        <img class="tp-login-shape-4" src="{{ Theme::asset()->url('images/login/login-shape-4.png') }}" alt="">
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8">
                <div class="tp-login-wrapper">
                    <div class="tp-login-top text-center mb-30">
                        <h3 class="tp-login-title">{{ __('Enter Verification Code') }}</h3>
                        <p>{{ __('Enter the 6-digit code sent to') }} <span class="fw-bold">{{ $sessionData['phone'] ?? '' }}</span></p>
                    </div>
                    
                    <div class="tp-login-bottom">
                        <form id="otp-form">
                            @csrf
                            <div class="tp-login-input-wrapper">
                                <div class="tp-login-input-box">
                                    <div class="tp-login-input">
                                        <input type="text" 
                                               id="otp" 
                                               name="otp_code" 
                                               maxlength="6" 
                                               pattern="[0-9]{6}"
                                               placeholder="{{ __('Enter 6-digit code') }}"
                                               style="text-align: center; font-size: 1.2rem; letter-spacing: 0.5rem;"
                                               required>
                                    </div>
                                    <div class="tp-login-input-title">
                                        <label for="otp">{{ __('Verification Code') }}</label>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" id="verification-id" name="verification_id">
                            <input type="hidden" id="session-token" name="session_token" value="{{ $sessionToken }}">
                            
                            <div class="tp-login-suggetions d-sm-flex align-items-center justify-content-between mb-20">
                                <div class="tp-login-remeber">
                                    <a href="#" id="resend-otp-link">{{ __('Resend Code') }}</a>
                                </div>
                                <div class="tp-login-forgot">
                                    <a href="{{ route('customer.password.phone') }}">{{ __('Back to phone input') }}</a>
                                </div>
                            </div>
                            
                            <div class="tp-login-bottom-action">
                                <button type="button" class="tp-btn w-100" id="verify-otp-btn">
                                    {{ __('Verify Code') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Success/Error Messages -->
<div id="success-message" style="display: none;" class="alert alert-success mt-3">
    {{ __('Verification successful! Redirecting...') }}
</div>
<div id="error-message" style="display: none;" class="alert alert-danger mt-3"></div>

{{-- Firebase SDK --}}
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>

<script>
// Firebase configuration
const firebaseConfig = {
    apiKey: "{{ config('services.firebase.api_key') }}",
    authDomain: "{{ config('services.firebase.auth_domain') }}",
    projectId: "{{ config('services.firebase.project_id') }}",
    storageBucket: "{{ config('services.firebase.storage_bucket') }}",
    messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
    appId: "{{ config('services.firebase.app_id') }}"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);
const auth = firebase.auth();
let confirmationResult;
const sessionToken = "{{ $sessionToken }}";

// Handle OTP verification
document.getElementById('verify-otp-btn').addEventListener('click', async function(e) {
    e.preventDefault();
    
    const otp = document.getElementById('otp').value;
    const verifyBtn = document.getElementById('verify-otp-btn');
    
    if (!otp || otp.length !== 6) {
        showError('{{ __("Please enter a valid 6-digit code") }}');
        return;
    }
    
    verifyBtn.disabled = true;
    verifyBtn.textContent = '{{ __("Verifying...") }}';
    
    try {
        // Get Firebase confirmation result from session storage
        const confirmationData = sessionStorage.getItem('firebase_confirmation_' + sessionToken);
        if (!confirmationData) {
            showError('{{ __("Session expired. Please start over.") }}');
            setTimeout(() => window.location.href = '{{ route("customer.password.phone") }}', 2000);
            return;
        }
        
        // For now, just verify with backend since Firebase confirmation is complex to persist
        const response = await fetch('{{ route("customer.password.phone.verify") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                otp_code: otp,
                session_token: sessionToken
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('success-message').style.display = 'block';
            setTimeout(() => window.location.href = data.redirect_url, 1500);
        } else {
            showError(data.message);
            verifyBtn.disabled = false;
            verifyBtn.textContent = '{{ __("Verify Code") }}';
        }
        
    } catch (error) {
        console.error('Error:', error);
        showError('{{ __("Invalid verification code. Please try again.") }}');
        verifyBtn.disabled = false;
        verifyBtn.textContent = '{{ __("Verify Code") }}';
    }
});

// Handle resend OTP
document.getElementById('resend-otp-link').addEventListener('click', function(e) {
    e.preventDefault();
    window.location.href = '{{ route("customer.password.phone") }}';
});

function showError(message) {
    const errorDiv = document.getElementById('error-message');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}
</script>
