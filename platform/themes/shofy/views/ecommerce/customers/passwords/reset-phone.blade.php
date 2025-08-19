@php
    Theme::set('breadcrumbHeight', 100);
    Theme::set('pageTitle', __('Set New Password'));
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
                        <h3 class="tp-login-title">{{ __('Set New Password') }}</h3>
                        <p>{{ __('Create a strong password for your account.') }}</p>
                    </div>
                    
                    @if(session('error'))
                        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
                    @endif

                    <div class="tp-login-bottom">
                        <form method="POST" action="{{ route('customer.password.phone.reset.post') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="tp-login-input-wrapper">
                                <div class="tp-login-input-box">
                                    <div class="tp-login-input">
                                        <input type="password"
                                               id="password"
                                               name="password"
                                               placeholder="{{ __('New Password') }}"
                                               required
                                               minlength="8">
                                    </div>
                                    <div class="tp-login-input-title">
                                        <label for="password">{{ __('New Password') }}</label>
                                    </div>
                                    @error('password')
                                        <div class="tp-login-input-error text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="tp-login-input-wrapper">
                                <div class="tp-login-input-box">
                                    <div class="tp-login-input">
                                        <input type="password"
                                               id="password_confirmation"
                                               name="password_confirmation"
                                               placeholder="{{ __('Confirm Password') }}"
                                               required
                                               minlength="8">
                                    </div>
                                    <div class="tp-login-input-title">
                                        <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tp-login-suggetions mb-20">
                                <small class="text-muted">{{ __('Password must be at least 8 characters') }}</small>
                            </div>

                            <div class="tp-login-bottom-action">
                                <button type="submit" class="tp-btn w-100">{{ __('Reset Password') }}</button>
                            </div>
                            
                            <div class="tp-login-register text-center mt-20">
                                <a href="{{ route('customer.login') }}">{{ __('Back to Login') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
