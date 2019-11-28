@include('partials/header')

@include('partials/common')

<script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
    window.api_host = "{!! $api_host !!}"
</script>
<!-- Raven Code -->
@if(env('APP_ENV') === 'production')
    <script src="{{$cdnDashboardUrl}}/dist/raven-entry.js"></script>
@endif

<script src="{{$cdnDashboardUrl}}/dist/merchantLA-entry.js"></script>

@include('partials/footer')
