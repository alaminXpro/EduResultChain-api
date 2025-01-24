<div class="card">
    <h6 class="card-header">@lang('General')</h6>

    <div class="card-body">
        <form action="{{ route('settings.auth.update') }}" method="POST" id="registration-settings-form">
        @csrf

        <div class="form-group mb-4">
            <div class="d-flex align-items-center">
                <div class="switch">
                    <input type="hidden" value="0" name="reg_enabled">

                    <input
                        type="checkbox" name="reg_enabled"
                        id="switch-reg-enabled"
                        class="switch" value="1"
                        {{ setting('reg_enabled') ? 'checked' : '' }}>

                    <label for="switch-reg-enabled"></label>
                </div>
                <div class="ml-3 d-flex flex-column">
                    <label class="mb-0">@lang('Allow Registration')</label>
                </div>
            </div>
        </div>

        <div class="form-group my-4">
            <div class="d-flex align-items-center">
                <div class="switch">
                    <input type="hidden" value="0" name="tos">
                    <input
                        value="1"
                        type="checkbox" name="tos"
                        id="switch-tos"
                        class="switch"
                        {{ setting('tos') ? 'checked' : '' }}>
                    <label for="switch-tos"></label>
                </div>
                <div class="ml-3 d-flex flex-column">
                    <label class="mb-0">@lang('Terms & Conditions')</label>
                    <small class="pt-0 text-muted">
                        @lang('The user has to confirm that he agrees with terms and conditions in order to create an account.')
                    </small>
                </div>
            </div>
        </div>

        <div class="form-group my-4">
            <div class="d-flex align-items-center">
                <div class="switch">
                    <input type="hidden" value="0" name="reg_email_confirmation">
                    <input
                        value="1"
                        type="checkbox" name="reg_email_confirmation"
                        id="switch-reg-email-confirm"
                        class="switch"
                        {{ setting('reg_email_confirmation') ? 'checked' : '' }}>
                    <label for="switch-reg-email-confirm"></label>
                </div>
                <div class="ml-3 d-flex flex-column">
                    <label class="mb-0">
                        @lang('Email Confirmation')
                    </label>
                    <small class="text-muted">
                        @lang('Require email confirmation from your newly registered users.')
                    </small>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">
            @lang('Update')
        </button>
    </form>
    </div>
</div>
