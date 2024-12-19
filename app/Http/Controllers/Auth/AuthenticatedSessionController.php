<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Events\VerifyReCaptchaToken;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Utility;
use App\Models\User;
use App\Models\Store;
use App\Models\Plan;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function __construct()
    {
        if(!file_exists(storage_path() . "/installed"))
        {
            header('location:install');
            die;
        }
    }


    public function store(LoginRequest $request)
    {
        $settings = Utility::settings();
        $validation=[];
        if(isset($settings['RECAPTCHA_MODULE']) && $settings['RECAPTCHA_MODULE'] == 'yes')
        {
            if($settings['google_recaptcha_version'] == 'v2'){
                $validation['g-recaptcha-response'] = 'required';
            } elseif ($settings['google_recaptcha_version'] == 'v3'){
                $result = event(new VerifyReCaptchaToken($request));
                if (!isset($result[0]['status']) || $result[0]['status'] != true) {
                    $key = 'g-recaptcha-response';
                    $request->merge([$key => null]);
                    $validation['g-recaptcha-response'] = 'required';
                }
            }else{
                $validation=[];
            }
        }
         else
        {
            $validation=[];
        }
        $this->validate($request, $validation);

        $request->authenticate();

        $request->session()->regenerate();
        $user = Auth::user();
        if($user->delete_status == 1)
        {
            auth()->logout();
        }
        if (isset($user->is_enable_login) && $user->is_enable_login == 0) {
            auth()->logout();
            return redirect('/login'.'/en')->with('status', __('Your Account has been Deactivated. Please contact your Site Admin.!')); 
        }

   
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function showLoginForm($lang = '')
    {
        if(empty($lang))
        {
            $lang = Utility::getValByName('default_language');
        }
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';
        if (empty($lang))
        {
        $lang = Utility::getValByName('default_language');
        }
        \App::setLocale($lang);

        return view('auth.login', compact('lang'));
    }

    public function showLinkRequestForm($lang = '')
    {
        if(empty($lang))
        {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.forgot-password', compact('lang'));
        /*return view('auth.passwords.email', compact('lang'));*/
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
