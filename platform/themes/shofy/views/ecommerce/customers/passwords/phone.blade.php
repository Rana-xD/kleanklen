{!! $form->renderForm() !!}

<!-- Success/Error Messages -->
<div id="success-message" style="display: none;" class="alert alert-success mt-3">
    {{ __('OTP sent successfully! Redirecting to verification page...') }}
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

// Setup reCAPTCHA
let recaptchaVerifier;
let confirmationResult;
let sessionToken;

window.onload = function() {
    try {
        recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
            'size': 'normal',
            'callback': (response) => {
                // reCAPTCHA solved
                document.getElementById('send-otp-btn').disabled = false;
            },
            'expired-callback': () => {
                // reCAPTCHA expired
                document.getElementById('send-otp-btn').disabled = true;
            }
        });
        
        recaptchaVerifier.render().then(() => {
            console.log('reCAPTCHA rendered successfully');
        }).catch((error) => {
            console.error('reCAPTCHA render error:', error);
            showError('Failed to load reCAPTCHA. Please refresh the page.');
        });
    } catch (error) {
        console.error('reCAPTCHA initialization error:', error);
        showError('Failed to initialize reCAPTCHA. Please refresh the page.');
    }
};

// Handle phone form submission
async function sendOTP() {
    const phone = document.getElementById('phone').value;
    const sendBtn = document.getElementById('send-otp-btn');
    const sendOtpUrl = '{{ route("customer.password.phone.send") }}';
    
    console.log('Send OTP URL:', sendOtpUrl);
    
    sendBtn.disabled = true;
    sendBtn.textContent = '{{ __("Sending...") }}';
    
    try {
        // First, check if customer exists
        const response = await fetch(sendOtpUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ phone: phone })
        });
        
        if (!response.ok) {
            // Helpful debugging for 404/other statuses
            const text = await response.text();
            console.error('Send OTP failed. Status:', response.status, 'Body:', text);
            let errMsg;
            if (response.status === 404) {
                errMsg = "{{ __('Endpoint not found. Please refresh and try again.') }}";
            } else {
                errMsg = "{{ __('Failed to send verification code. Please try again.') }}";
            }
            showError(errMsg);
            sendBtn.disabled = false;
            sendBtn.textContent = '{{ __("Send Verification Code") }}';
            return;
        }

        const data = await response.json();
        
        if (data.error) {
            showError(data.message);
            sendBtn.disabled = false;
            sendBtn.textContent = '{{ __("Send Verification Code") }}';
            return;
        }
        
        // Store session token
        sessionToken = data.session_token;
        
        // Validate and format phone number for Firebase
        if (!isValidCambodiaPhone(phone)) {
            showError('{{ __("Please enter a valid Cambodia phone number (e.g., 093 123 321)") }}');
            sendBtn.disabled = false;
            sendBtn.textContent = '{{ __("Send Verification Code") }}';
            return;
        }
        
        const formattedPhone = formatPhoneNumber(phone);
        console.log('Formatted phone for Firebase:', formattedPhone);
        
        // Send OTP via Firebase
        console.log('Attempting to send OTP to:', formattedPhone);
        confirmationResult = await auth.signInWithPhoneNumber(formattedPhone, recaptchaVerifier);
        
        console.log('OTP sent successfully, preparing redirect');
        
        // Reset button state
        sendBtn.disabled = false;
        sendBtn.textContent = '{{ __("Send Verification Code") }}';
        
        // Store Firebase confirmation in session storage for OTP page
        sessionStorage.setItem('firebase_confirmation_' + sessionToken, JSON.stringify({
            timestamp: Date.now(),
            phone: phone
        }));
        
        // Redirect to OTP verification page
        window.location.href = data.redirect_url;
        
    } catch (error) {
        console.error('Error:', error);
        showError('{{ __("Failed to send verification code. Please try again.") }}');
        sendBtn.disabled = false;
        sendBtn.textContent = '{{ __("Send Verification Code") }}';
    }
}

// Handle OTP verification
document.addEventListener('click', function(e) {
    if (e.target.id === 'verify-otp-btn') {
        e.preventDefault();
        verifyOTP();
    } else if (e.target.id === 'resend-otp-btn') {
        e.preventDefault();
        resendOTP();
    }
});

async function verifyOTP() {
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
}

async function resendOTP() {
    const phone = document.getElementById('phone').value;
    const resendBtn = document.getElementById('resend-otp-btn');
    const form = document.querySelector('form');
    
    resendBtn.disabled = true;
    resendBtn.textContent = '{{ __("Resending...") }}';
    
    try {
        // Reset reCAPTCHA
        recaptchaVerifier.clear();
        recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
            'size': 'normal',
            'callback': (response) => {
                document.getElementById('send-otp-btn').disabled = false;
            }
        });
        await recaptchaVerifier.render();
        
        const formattedPhone = formatPhoneNumber(phone);
        confirmationResult = await auth.signInWithPhoneNumber(formattedPhone, recaptchaVerifier);
        
        resendBtn.disabled = false;
        resendBtn.textContent = '{{ __("Resend Code") }}';
        
    } catch (error) {
        console.error('Error:', error);
        showError('{{ __("Failed to resend code. Please try again.") }}');
        resendBtn.disabled = false;
        resendBtn.textContent = '{{ __("Resend Code") }}';
    }
}

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
