<div class="card">
    <h6 class="card-header">
        @lang('General')
    </h6>

    <div class="card-body">
        <form method="POST" action="{{ route('settings.auth.update') }}" id="auth-general-settings-form">
            @csrf

        <div class="form-group mb-4">
            <div class="d-flex align-items-center">
                 <div class="switch">
                     <input type="hidden" value="0" name="remember_me">
                     <input type="checkbox" name="remember_me" id="switch-remember-me" class="switch"
                               value="1" {{ setting('remember_me') ? 'checked' : '' }}>
                     <label for="switch-remember-me"></label>
                 </div>
                <div class="ml-3 d-flex flex-column">
                    <label class="mb-0">@lang('Allow "Remember Me"')</label>
                    <small class="pt-0 text-muted">
                        @lang("Should 'Remember Me' checkbox be displayed on login form?")
                    </small>
                </div>
            </div>
        </div>

        <div class="form-group my-4">
            <div class="d-flex align-items-center">
                <div class="switch">
                    <input type="hidden" value="0" name="forgot_password">
                    <input type="checkbox" name="forgot_password" id="switch-forgot-pass" class="switch"
                           value="1" {{ setting('forgot_password') ? 'checked' : '' }}>
                    <label for="switch-forgot-pass"></label>
                </div>
                <div class="ml-3 d-flex flex-column">
                    <label class="mb-0">@lang('Forgot Password')</label>
                    <small class="pt-0 text-muted">
                        @lang('Enable/Disable forgot password feature.')
                    </small>
                </div>
            </div>
        </div>

        <div class="form-group my-4">
            <label for="login_reset_token_lifetime">
                @lang('Reset Token Lifetime') <br>
                <small class="text-muted">
                    @lang('Number of minutes that the reset token should be considered valid.')
                </small>
            </label>
            <input type="text" name="login_reset_token_lifetime"
                   class="form-control input-solid" value="{{ setting('login_reset_token_lifetime', 30) }}">
        </div>

        <div class="form-group my-4">
            <label for="login_reset_token_lifetime">
                @lang('Max Number of Active Sessions') <br>
                <small class="text-muted">
                    @lang('Maximum number of active sessions per user. Set to 0 to allow unlimited number of active sessions.')
                    <br>
                    @lang('Only applies when using database session driver.')
                </small>
            </label>
            <input type="text" name="max_active_sessions"
                   class="form-control input-solid" value="{{ setting('max_active_sessions', 0) }}">
        </div>

        <button type="submit" class="btn btn-primary">
            @lang('Update')
        </button>
        </form>
    </div>
</div>
