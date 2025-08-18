<?php

namespace Botble\Ecommerce\Http\Controllers\Customers;

use Botble\Ecommerce\Forms\Fronts\Auth\PhonePasswordResetForm;
use Botble\Ecommerce\Http\Controllers\BaseController;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\CustomerPasswordReset;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;

class PhonePasswordResetController extends BaseController
{
    protected $firebaseAuth;
    
    public function __construct()
    {
        $this->middleware('customer.guest');
        $this->middleware('throttle:5,1')->only('sendOtp');
        $this->firebaseAuth = null;
    }
    
    /**
     * Get Firebase Auth instance
     */
    private function getFirebaseAuth()
    {
        if ($this->firebaseAuth !== null) {
            return $this->firebaseAuth;
        }
        
        try {
            $projectId = config('services.firebase.project_id');
            $credentialsPath = config('services.firebase.credentials_path');
            
            if (!$projectId || !$credentialsPath) {
                return null;
            }
            
            $fullCredentialsPath = storage_path($credentialsPath);
            
            if (!file_exists($fullCredentialsPath)) {
                return null;
            }
            
            $factory = (new \Kreait\Firebase\Factory())
                ->withServiceAccount($fullCredentialsPath);
            
            $this->firebaseAuth = $factory->createAuth();
            return $this->firebaseAuth;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Format Cambodia phone number to international format
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);
        
        // Cambodia phone validation and formatting
        if (str_starts_with($cleaned, '855')) {
            // Already has country code
            return '+' . $cleaned;
        } elseif (str_starts_with($cleaned, '0')) {
            // Local format starting with 0 (e.g., 093860146)
            return '+855' . substr($cleaned, 1);
        } elseif (strlen($cleaned) >= 8 && strlen($cleaned) <= 9) {
            // Local format without 0 (e.g., 93860146)
            return '+855' . $cleaned;
        }
        
        throw new \InvalidArgumentException('Invalid Cambodia phone number format');
    }
    
    /**
     * Validate Cambodia phone number
     */
    private function isValidCambodiaPhone($phone)
    {
        $cleaned = preg_replace('/\D/', '', $phone);
        
        // Check various Cambodia phone formats
        if (str_starts_with($cleaned, '855')) {
            // International format: +855xxxxxxxx (8-9 digits after 855)
            return strlen($cleaned) >= 11 && strlen($cleaned) <= 12;
        } elseif (str_starts_with($cleaned, '0')) {
            // Local format with 0: 0xxxxxxxx (9-10 digits total)
            return strlen($cleaned) >= 9 && strlen($cleaned) <= 10;
        } else {
            // Local format without 0: xxxxxxxx (8-9 digits)
            return strlen($cleaned) >= 8 && strlen($cleaned) <= 9;
        }
    }
    
    /**
     * Show phone number input form
     */
    public function showPhoneForm()
    {
        // Check if Firebase is configured
        $firebaseAuth = $this->getFirebaseAuth();
        if (!$firebaseAuth || !config('services.firebase.project_id')) {
            return redirect()->route('customer.password.reset')
                ->with('error', __('Phone-based password reset is not available. Please use email reset instead.'));
        }

        SeoHelper::setTitle(__('Reset Password via Phone'));

        Theme::breadcrumb()
            ->add(__('Reset Password'), route('customer.password.phone'));

        $form = PhonePasswordResetForm::create();

        return Theme::scope(
            'ecommerce.customers.passwords.phone',
            compact('form'),
            'plugins/ecommerce::themes.customers.passwords.phone'
        )->render();
    }
    
    /**
     * Show OTP verification form
     */
    public function showOtpForm(Request $request)
    {
        $sessionToken = $request->query('token');
        if (!$sessionToken || !Session::has("phone_reset_$sessionToken")) {
            return redirect()->route('customer.password.phone')
                ->with('error', __('Invalid or expired session. Please try again.'));
        }

        $sessionData = Session::get("phone_reset_$sessionToken");
        
        SeoHelper::setTitle(__('Enter Verification Code'));
        Theme::breadcrumb()
            ->add(__('Reset Password'), route('customer.password.phone'))
            ->add(__('Verify Code'), route('customer.password.phone.otp', ['token' => $sessionToken]));

        return Theme::scope(
            'ecommerce.customers.passwords.phone-otp',
            compact('sessionToken', 'sessionData'),
            'plugins/ecommerce::themes.customers.passwords.phone-otp'
        )->render();
    }

    /**
     * Send OTP to phone number
     */
    public function sendOtp(Request $request)
    {
        // Check if Firebase is configured
        $firebaseAuth = $this->getFirebaseAuth();
        if (!$firebaseAuth || !config('services.firebase.project_id')) {
            return response()->json([
                'error' => true,
                'message' => __('Phone-based password reset is not available.'),
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }
        
        $phone = $this->formatPhoneNumber($request->phone);
        
        // Validate Cambodia phone format
        if (!$this->isValidCambodiaPhone($request->phone)) {
            return response()->json([
                'error' => true,
                'message' => __('Please enter a valid Cambodia phone number (e.g., 093 123 321)'),
            ], 422);
        }
        
        // Check if customer exists - try both original and formatted phone numbers
        $customer = Customer::where('phone', $request->phone)->first();
        
        if (!$customer) {
            // Try with formatted international number
            $customer = Customer::where('phone', $phone)->first();
        }
        
        if (!$customer) {
            // Try with other possible formats
            $cleanPhone = preg_replace('/\D/', '', $request->phone);
            if (str_starts_with($cleanPhone, '0')) {
                // Try without leading zero
                $customer = Customer::where('phone', substr($cleanPhone, 1))->first();
            }
            if (!$customer && !str_starts_with($cleanPhone, '0')) {
                // Try with leading zero
                $customer = Customer::where('phone', '0' . $cleanPhone)->first();
            }
        }
        
        if (!$customer) {
            return response()->json([
                'error' => true,
                'message' => __('No account found with this phone number.'),
            ], 404);
        }
        
        try {
            // Generate session token for tracking
            $sessionToken = Str::random(60);
            
            // Store in session with customer ID
            Session::put("phone_reset_$sessionToken", [
                'token' => $sessionToken,
                'phone' => $phone,
                'customer_id' => $customer->id,
                'expires_at' => now()->addMinutes(10),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => __('OTP sent successfully.'),
                'session_token' => $sessionToken,
                'phone' => $this->maskPhoneNumber($request->phone),
                'redirect_url' => route('customer.password.phone.otp', ['token' => $sessionToken]),
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => __('Failed to send OTP. Please try again.'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Verify OTP code
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'verification_id' => 'nullable|string',
            'otp_code' => 'required|string|size:6',
            'session_token' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }
        
        $sessionKey = "phone_reset_{$request->session_token}";
        $sessionData = Session::get($sessionKey);
        
        if (!$sessionData ||
            ($sessionData['token'] ?? null) !== $request->session_token ||
            now()->isAfter($sessionData['expires_at'])) {
            return response()->json([
                'error' => true,
                'message' => __('Session expired. Please request a new OTP.'),
            ], 401);
        }
        
        try {
            // Generate password reset token
            $resetToken = Str::random(60);
            
            // Store reset token with expiry
            Session::put('phone_password_reset', [
                'token' => $resetToken,
                'customer_id' => $sessionData['customer_id'],
                'expires_at' => now()->addMinutes(30),
            ]);
            
            // Clear OTP session
            Session::forget($sessionKey);
            
            return response()->json([
                'success' => true,
                'message' => __('Phone verified successfully.'),
                'reset_token' => $resetToken,
                'redirect_url' => route('customer.password.phone.reset', ['token' => $resetToken]),
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => __('Invalid OTP code.'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 400);
        }
    }
    
    /**
     * Show reset password form
     */
    public function showResetForm($token)
    {
        $sessionData = Session::get('phone_password_reset');
        
        if (!$sessionData || 
            $sessionData['token'] !== $token ||
            now()->isAfter($sessionData['expires_at'])) {
            return redirect()->route('customer.password.phone')
                ->with('error', __('Invalid or expired reset token.'));
        }
        
        SeoHelper::setTitle(__('Set New Password'));

        Theme::breadcrumb()
            ->add(__('Set New Password'), route('customer.password.phone.reset', $token));

        return Theme::scope(
            'ecommerce.customers.passwords.reset-phone',
            ['token' => $token],
            'plugins/ecommerce::themes.customers.passwords.reset-phone'
        )->render();
    }
    
    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        $sessionData = Session::get('phone_password_reset');
        
        if (!$sessionData || 
            $sessionData['token'] !== $request->token ||
            now()->isAfter($sessionData['expires_at'])) {
            return back()->with('error', __('Invalid or expired reset token.'));
        }
        
        try {
            $customer = Customer::find($sessionData['customer_id']);
            
            if (!$customer) {
                return back()->with('error', __('Customer not found.'));
            }
            
            // Update password
            $customer->password = Hash::make($request->password);
            $customer->save();
            
            // Clear session
            Session::forget('phone_password_reset');
            
            return redirect()->route('customer.login')
                ->with('success', __('Password has been reset successfully. Please login with your new password.'));
                
        } catch (Exception $e) {
            return back()->with('error', __('Failed to reset password. Please try again.'));
        }
    }
    
    
    /**
     * Mask phone number for display
     */
    private function maskPhoneNumber($phone)
    {
        $length = strlen($phone);
        if ($length > 7) {
            return substr($phone, 0, -7) . 'xxx' . substr($phone, -4);
        }
        return $phone;
    }
}
