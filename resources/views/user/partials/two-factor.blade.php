@php
    $route = 'enable';
    $needsVerification = session('tab') == '2fa' && $user->needsTwoFactorVerification();

    if ($needsVerification) {
        $route = 'verify';
    } elseif ($user->twoFactorEnabled()) {
        $route = 'disable';
    }
@endphp

<form action="{{ route("two-factor.{$route}") }}" method="POST" id="two-factor-form">
    @csrf
    <input type="hidden" name="user" value="{{ $user->id }}">
    @if (!$user->twoFactorEnabled() && !$needsVerification)
        <button type="submit"
                class="btn btn-primary"
                data-toggle="loader"
                data-loading-text="@lang('Enabling...')">
            @lang('Enable')
        </button>
    @else
        @if ($user->twoFactorEnabled())
            <button type="submit"
                    class="btn btn-danger mt-2"
                    data-toggle="loader"
                    data-loading-text="@lang('Disabling...')">
                <i class="fa fa-close"></i>
                @lang('Disable')
            </button>
        @else
            <h3>@lang("Please finish configuring Two-Factor authentication below.")</h3>
            <div class="my-3 font-medium text-sm font-weight-bold">
                @lang("When two factor authentication is enabled, you will be prompted for a secure, random token during authentication.")
                @lang("You may retrieve this token from your phone's Authenticator application.")
            </div>

            <div class="my-3 font-medium text-sm">
                @lang("To finish enabling two factor authentication, scan the following QR code using your phone's authenticator application or enter the setup key and provide the generated OTP code.")
            </div>

            <div class="my-5 d-flex align-items-center justify-content-center w-100">
                {!! $user->twoFactorQrCodeSvg() !!}
            </div>

            <input type="text"
                   name="code"
                   id="code"
                   class="form-control input-solid mb-4"
                   placeholder="@lang('Code')"
                   value="{{ old('code') }}">

            <button type="submit"
                    class="btn btn-primary mr-2"
                    data-toggle="loader"
                    data-loading-text="@lang('Confirming...')">
                <i class="fa fa-close"></i>
                @lang('Confirm')
            </button>
        @endif
    @endif
</form>
