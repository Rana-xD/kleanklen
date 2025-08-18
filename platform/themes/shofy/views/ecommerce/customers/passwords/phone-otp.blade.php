@php
    SeoHelper::setTitle(__('Enter Verification Code'));
    Theme::breadcrumb()
        ->add(__('Reset Password'), route('customer.password.phone'))
        ->add(__('Verify Code'));
@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="auth-card">
                <div class="auth-card__body">
                    <div class="auth-card__header">
                        <div class="auth-card__icon">
                            <i class="ti ti-phone"></i>
                        </div>
                        <h3 class="auth-card__title">{{ __('Enter Verification Code') }}</h3>
                        <p class="auth-card__description">
                            {{ __('Enter the 6-digit code sent to') }} <span class="fw-bold">{{ $sessionData['phone'] ?? '' }}</span>
                        </p>
                    </div>
                    
                    <form id="otp-form">
                        @csrf
                        <div class="mb-3">
                            <label for="otp" class="form-label">{{ __('Verification Code') }}</label>
                            <input type="text" 
                                   class="form-control text-center" 
                                   id="otp" 
                                   name="otp_code" 
                                   maxlength="6" 
                                   pattern="[0-9]{6}"
                                   placeholder="000000"
                                   style="font-size: 1.2rem; letter-spacing: 0.5rem;"
                                   required>
                        </div>
                        
                        <input type="hidden" id="verification-id" name="verification_id">
                        <input type="hidden" id="session-token" name="session_token" value="{{ $sessionToken }}">
                        
                        <div class="d-grid mb-3">
                            <button type="button" class="btn btn-primary btn-auth-submit" id="verify-otp-btn">
                                {{ __('Verify Code') }}
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-link" id="resend-otp-btn">
                                {{ __('Resend Code') }}
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('customer.password.phone') }}" class="text-decoration-underline">{{ __('Back to phone input') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
document.getElementById('resend-otp-btn').addEventListener('click', function() {
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
