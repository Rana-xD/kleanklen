@php
    Theme::set('breadcrumbHeight', 100);
    Theme::set('pageTitle', __('Set New Password'));
@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('Set New Password') }}</h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('customer.password.phone.reset.post') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        
                        <div class="form-group mb-3">
                            <label for="password">{{ __('New Password') }}</label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   minlength="8">
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                {{ __('Password must be at least 8 characters') }}
                            </small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required 
                                   minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block w-100">
                            {{ __('Reset Password') }}
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="{{ route('customer.login') }}">{{ __('Back to Login') }}</a>
            </div>
        </div>
    </div>
</div>
