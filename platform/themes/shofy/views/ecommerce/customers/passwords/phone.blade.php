@php
    Theme::set('breadcrumbHeight', 100);
    Theme::set('pageTitle', __('Reset Password via Phone'));
@endphp

<div id="phone-step">
    <p>{{ __('Enter your phone number to receive a verification code.') }}</p>
    
    <form id="phone-form">
        @csrf
        <div class="form-group mb-3">
            <label for="phone">{{ __('Phone Number') }}</label>
            <input type="tel" 
                   class="form-control" 
                   id="phone" 
                   name="phone" 
                   placeholder="093 123 321"
                   required>
            <small class="form-text text-muted">
                {{ __('Enter your Cambodia phone number (e.g., 093 123 321)') }}
            </small>
        </div>
        
        <div id="recaptcha-container" class="mb-3"></div>
        
        <button type="submit" class="btn btn-primary w-100" id="send-otp-btn" disabled>
            {{ __('Send Verification Code') }}
        </button>
    </form>
</div>

<div id="otp-step" style="display: none;">
    <p>{{ __('Enter the 6-digit code sent to') }} <span id="phone-display"></span></p>
    
    <form id="otp-form">
        @csrf
        <div class="form-group mb-3">
            <label for="otp">{{ __('Verification Code') }}</label>
            <input type="text" 
                   class="form-control text-center" 
                   id="otp" 
                   name="otp_code" 
                   maxlength="6" 
                   pattern="[0-9]{6}"
                   placeholder="000000"
                   required>
        </div>
        
        <input type="hidden" id="verification-id" name="verification_id">
        <input type="hidden" id="session-token" name="session_token">
        
        <button type="submit" class="btn btn-primary w-100" id="verify-otp-btn">
            {{ __('Verify Code') }}
        </button>
        
        <div class="text-center mt-3">
            <button type="button" class="btn btn-link" id="resend-otp-btn">
                {{ __('Resend Code') }}
            </button>
        </div>
    </form>
</div>

<div id="success-message" style="display: none;" class="alert alert-success">
    {{ __('Verification successful! Redirecting...') }}
</div>

<div id="error-message" style="display: none;" class="alert alert-danger"></div>

<div class="text-center mt-3">
    <a href="{{ route('customer.login') }}">{{ __('Back to Login') }}</a>
</div>

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

// Setup reCAPTCHA
let recaptchaVerifier;
let confirmationResult;
let sessionToken;

window.onload = function() {
    recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
        'size': 'normal',
        'callback': (response) => {
            // reCAPTCHA solved
            document.getElementById('send-otp-btn').disabled = false;
        }
    });
    
    recaptchaVerifier.render();
};

// Handle phone form submission
document.getElementById('phone-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const phone = document.getElementById('phone').value;
    const sendBtn = document.getElementById('send-otp-btn');
    
    sendBtn.disabled = true;
    sendBtn.textContent = '{{ __("Sending...") }}';
    
    try {
        // First, check if customer exists
        const response = await fetch('{{ route("customer.password.phone.send") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ phone: phone })
        });
        
        const data = await response.json();
        
        if (data.error) {
            showError(data.message);
            sendBtn.disabled = false;
            sendBtn.textContent = '{{ __("Send Verification Code") }}';
            return;
        }
        
        // Store session token
        sessionToken = data.session_token;
        document.getElementById('session-token').value = sessionToken;
        document.getElementById('phone-display').textContent = data.phone;
        
        // Validate and format phone number for Firebase
        if (!isValidCambodiaPhone(phone)) {
            showError('{{ __("Please enter a valid Cambodia phone number (e.g., 093 123 321)") }}');
            sendBtn.disabled = false;
            sendBtn.textContent = '{{ __("Send Verification Code") }}';
            return;
        }
        
        const formattedPhone = formatPhoneNumber(phone);
        document.getElementById('phone-display').textContent = phone; // Show original input
        console.log('Formatted phone for Firebase:', formattedPhone);
        
        // Send OTP via Firebase
        confirmationResult = await auth.signInWithPhoneNumber(formattedPhone, recaptchaVerifier);
        
        // Show OTP input
        document.getElementById('phone-step').style.display = 'none';
        document.getElementById('otp-step').style.display = 'block';
        
    } catch (error) {
        console.error('Error:', error);
        showError('{{ __("Failed to send verification code. Please try again.") }}');
        sendBtn.disabled = false;
        sendBtn.textContent = '{{ __("Send Verification Code") }}';
    }
});

// Handle OTP verification
document.getElementById('otp-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const otp = document.getElementById('otp').value;
    const verifyBtn = document.getElementById('verify-otp-btn');
    
    verifyBtn.disabled = true;
    verifyBtn.textContent = '{{ __("Verifying...") }}';
    
    try {
        // Verify OTP with Firebase
        const result = await confirmationResult.confirm(otp);
        const user = result.user;
        
        // Get ID token
        const idToken = await user.getIdToken();
        document.getElementById('verification-id').value = idToken;
        
        // Verify with backend
        const response = await fetch('{{ route("customer.password.phone.verify") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                verification_id: idToken,
                otp_code: otp,
                session_token: sessionToken
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('success-message').style.display = 'block';
            document.getElementById('otp-step').style.display = 'none';
            
            // Redirect to password reset form
            setTimeout(() => {
                window.location.href = '{{ route("customer.password.phone.reset", "") }}/' + data.reset_token;
            }, 2000);
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
    document.getElementById('phone-step').style.display = 'block';
    document.getElementById('otp-step').style.display = 'none';
    document.getElementById('error-message').style.display = 'none';
    
    // Reset reCAPTCHA
    recaptchaVerifier.clear();
    recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
        'size': 'normal',
        'callback': (response) => {
            document.getElementById('send-otp-btn').disabled = false;
        }
    });
    recaptchaVerifier.render();
});

// Phone number validation and formatting for Cambodia
function formatPhoneNumber(phone) {
    // Remove all non-digit characters
    const cleaned = phone.replace(/\D/g, '');
    
    // Cambodia phone validation and formatting
    if (cleaned.startsWith('855')) {
        // Already has country code
        return '+' + cleaned;
    } else if (cleaned.startsWith('0')) {
        // Local format starting with 0 (e.g., 093860146)
        return '+855' + cleaned.substring(1);
    } else if (cleaned.length >= 8 && cleaned.length <= 9) {
        // Local format without 0 (e.g., 93860146)
        return '+855' + cleaned;
    } else {
        throw new Error('Invalid Cambodia phone number format');
    }
}

// Validate Cambodia phone number
function isValidCambodiaPhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    
    // Check various Cambodia phone formats
    if (cleaned.startsWith('855')) {
        // International format: +855xxxxxxxx (8-9 digits after 855)
        return cleaned.length >= 11 && cleaned.length <= 12;
    } else if (cleaned.startsWith('0')) {
        // Local format with 0: 0xxxxxxxx (9-10 digits total)
        return cleaned.length >= 9 && cleaned.length <= 10;
    } else {
        // Local format without 0: xxxxxxxx (8-9 digits)
        return cleaned.length >= 8 && cleaned.length <= 9;
    }
}

function showError(message) {
    const errorDiv = document.getElementById('error-message');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}
</script>
