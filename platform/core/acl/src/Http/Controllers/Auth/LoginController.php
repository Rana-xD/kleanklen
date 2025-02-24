<?php

namespace Botble\ACL\Http\Controllers\Auth;

use Botble\ACL\Forms\Auth\LoginForm;
use Botble\ACL\Http\Requests\LoginRequest;
use Botble\ACL\Models\User;
use Botble\ACL\Traits\AuthenticatesUsers;
use Botble\Base\Http\Controllers\BaseController;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;

/**
 * Controller handling user authentication
 * 
 * This controller manages all authentication-related operations including:
 * - User login with username/email
 * - Login attempt throttling
 * - Session management
 * - User logout
 * 
 * Uses AuthenticatesUsers trait for core authentication functionality
 */
class LoginController extends BaseController
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login
     * 
     * @var string
     */
    protected string $redirectTo = '/';

    /**
     * Constructor for LoginController
     * 
     * Sets up middleware to restrict access to logged-in users
     * Sets the post-login redirect path
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);

        $this->redirectTo = route('dashboard.index');
    }

    /**
     * Show the application's login form
     * 
     * @return mixed Returns the rendered login form
     */
    public function showLoginForm()
    {
        $this->pageTitle(trans('core/acl::auth.login_title'));

        return LoginForm::create()->renderForm();
    }

    /**
     * Handle a login request to the application
     * 
     * Validates credentials, checks for account activation,
     * handles login attempts throttling, and manages user session
     * 
     * @param LoginRequest $request The validated login request
     * @return mixed Returns response based on authentication result
     */
    public function login(LoginRequest $request)
    {
        $request->merge([$this->username() => $request->input('username')]);

        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request);
        }

        $user = User::query()->where([$this->username() => $request->input($this->username())])->first();
        if (! empty($user)) {
            if (! $user->activated) {
                return $this->httpResponse()
                    ->setError()
                    ->setMessage(trans('core/acl::auth.login.not_active'));
            }
        }

        return app(Pipeline::class)->send($request)
            ->through(apply_filters('core_acl_login_pipeline', [
                function (Request $request, Closure $next) {
                    if ($this->guard()->attempt(
                        $this->credentials($request),
                        $request->filled('remember')
                    )) {
                        return $next($request);
                    }

                    $this->incrementLoginAttempts($request);

                    return $this->sendFailedLoginResponse();
                },
            ]))
            ->then(function (Request $request) {
                Auth::guard()->user()->update(['last_login' => Carbon::now()]);

                if (! session()->has('url.intended')) {
                    session()->flash('url.intended', url()->current());
                }

                return $this->sendLoginResponse($request);
            });
    }

    /**
     * Get the login username/email field based on input type
     * 
     * Determines whether the user is logging in with email or username
     * 
     * @return string Returns 'email' or 'username'
     */
    public function username()
    {
        return filter_var(request()->input('username'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    /**
     * Log the user out of the application
     * 
     * Handles session invalidation and cleanup
     * 
     * @param Request $request The logout request
     * @return mixed Returns response with logout status
     */
    public function logout(Request $request)
    {
        do_action(AUTH_ACTION_AFTER_LOGOUT_SYSTEM, $request, $request->user());

        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->httpResponse()
            ->setNextRoute('access.login')
            ->setMessage(trans('core/acl::auth.login.logout_success'));
    }
}
