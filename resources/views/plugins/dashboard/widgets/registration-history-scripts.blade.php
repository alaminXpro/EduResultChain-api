<script>
    var users = @json(array_values($usersPerMonth));
    var months = @json(array_keys($usersPerMonth));
    var trans = {
        chartLabel: "{{ __('Registration History')  }}",
        new: "{{ __('new') }}",
        user: "{{ __('user') }}",
        users: "{{ __('users') }}"
    };
</script>

<script src="{{ asset('assets/js/chart.min.js') }}"></script>
<script src="{{ asset('assets/js/as/dashboard-admin.js') }}"></script>
