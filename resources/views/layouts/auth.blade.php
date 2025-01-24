<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('page-title') - {{ setting('app_name') }}</title>

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/fontawesome-all.min.css') }}" rel="stylesheet">

    @yield('header-scripts')

    @hook('auth:styles')
</head>
<body class="auth">

    <div class="d-flex justify-content-end py-2 pr-4">
        @include('partials.locale-dropdown')
    </div>

    <div class="container">
        @yield('content')
    </div>

    <script src="{{ asset('assets/js/vendor.js') }}"></script>
    <script src="{{ asset('assets/js/as/app.js') }}"></script>
    <script src="{{ asset('assets/js/as/btn.js') }}"></script>
    @yield('scripts')
    @hook('auth:scripts')
</body>
</html>
