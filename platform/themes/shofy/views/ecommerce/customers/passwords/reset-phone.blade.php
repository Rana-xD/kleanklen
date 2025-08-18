@php
    Theme::set('breadcrumbHeight', 100);
    Theme::set('pageTitle', __('Set New Password'));
@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="auth-card">
                <div class="auth-card__body">
                    <div class="auth-card__header">
                        <div class="auth-card__icon">
                            <i class="ti ti-lock"></i>
                        </div>
                        <h3 class="auth-card__title">{{ __('Set New Password') }}</h3>
                        <p class="auth-card__description">{{ __('Create a strong password for your account.') }}</p>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('customer.password.phone.reset.post') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('New Password') }}</label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   required
                                   minlength="8">
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Password must be at least 8 characters') }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required
                                   minlength="8">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-auth-submit">{{ __('Reset Password') }}</button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ route('customer.login') }}" class="text-decoration-underline">{{ __('Back to Login') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
