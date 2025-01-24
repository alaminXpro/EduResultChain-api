<?php
    use Vanguard\Support\Locale;
?>
<li class="nav-item dropdown d-flex align-items-center">
    <a class="nav-link dropdown-toggle text-uppercase"
       href="#"
       id="navbarDropdown"
       role="button"
       data-toggle="dropdown"
       aria-haspopup="true"
       aria-expanded="false">
        <img src="{{ Locale::flagUrl(session()->get('locale') ?: config('app.locale')) }}" width="30" />
        <span class="ml-1">{{ session()->get('locale') }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-right position-absolute p-0" aria-labelledby="navbarDropdown">
        @foreach(Locale::AVAILABLE_LOCALES as $locale)
            <a class="dropdown-item py-2 text-uppercase" href="?lang={{ $locale }}">
                <img src="{{ Locale::flagUrl($locale) }}" alt="{{ $locale }}" width="30">
                <span class="ml-1">{{ $locale }}</span>
            </a>
        @endforeach
    </div>
</li>
