@if ($socialProviders)
<?php $colSize = 12 / count($socialProviders); ?>

<div class="row pb-3 pt-2">
    @if (in_array('google', $socialProviders))
    <div class="col-{{ $colSize }} d-flex align-items-center justify-content-center">
        <a href="{{ url('auth/google/login') }}" class="btn-google">
            <i class="fab fa-google fa-2x"></i>
        </a>
    </div>
    @endif
    @if (in_array('github', $socialProviders))
    <div class="col-{{ $colSize }} d-flex align-items-center justify-content-center">
        <a href="{{ url('auth/github/login') }}" style="color: #24292e;">
            <i class="fab fa-github fa-2x"></i>
        </a>
    </div>
    @endif
</div>

@endif
