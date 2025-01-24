<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-1">@lang('Two-Factor Authentication')</h5>

        <small class="text-muted d-block mb-4">
            @lang('Enable/Disable Two-Factor Authentication for the application.')
        </small>

        @if (setting('2fa.enabled'))
            <form method="POST" action="{{ route('settings.auth.2fa.disable') }}" id="auth-2fa-settings-form">
                @csrf
                <button type="submit"
                        class="btn btn-danger"
                        data-toggle="loader"
                        data-loading-text="@lang('Disabling...')">
                    @lang('Disable')
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('settings.auth.2fa.enable') }}" id="auth-2fa-settings-form">
                @csrf
                <button type="submit"
                        class="btn btn-primary"
                        data-toggle="loader"
                        data-loading-text="@lang('Enabling...')">
                    @lang('Enable')
                </button>
            </form>
        @endif
    </div>
</div>
