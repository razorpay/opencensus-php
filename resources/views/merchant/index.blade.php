<?php
    $isOrgHDFC = (json_decode($org, true)['custom_code']) === "hdfc";
?>
@include('partials/header')

@if ($isConfirmed and $isPreSignupComplete)

@else
  <link rel='stylesheet' href="{{$cdnDashboardUrl}}/css/generated/signup.css" type='text/css' />
@endif

@include('partials/common')

<script>
  document.domain = window.location.hostname.split(".").slice(-2).join(".");

  window.RZP = window.RZP || {};

  if (
    window.parent !== window &&
    ~window.parent.location.href.indexOf("{{ config('app.banking_service_url') }}")
  ) {

    document.write("<script src='{{ config('app.banking_service_url') }}/dist/pgClient.js'>\<\/script>");

    window.RZP.appHost = "{{ config('app.banking_service_url') }}";
    window.RZP.appName = "businessbanking";

    window.rzpTicketSystem = {
      hostname: window.RZP.appHost
    };
  }
</script>

@if ($isConfirmed and $isPreSignupComplete)
  <script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
    window.notifications = {!! $notifications !!};
    window.api_host = "{!! $api_host !!}"
    window.custom_notes = {!! $custom_notes !!};
    window.pl_expiry_in_hrs = {!! $pl_expiry_in_hrs !!};
    window.pl_custom_labels = {!! $pl_custom_labels !!};
  </script>
  <!-- Raven Code -->
  @if(env('APP_ENV') === 'production')
    <script src="{{$cdnDashboardUrl}}/dist/raven-entry.js"></script>
  @endif
  <script src="{{$cdnDashboardUrl}}/dist/merchant-entry.js"></script>
@else
  <script src='{{$cdnDashboardUrl}}/js/generated/signup.js'></script>
@endif


@include('partials/footer')
