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

    document.write(
      "<link rel='stylesheet' href='{{ config('app.banking_service_url') }}/dist/pgClient.css' type='text/css'/>"
    );

    document.write("<script src='{{ config('app.banking_service_url') }}/dist/pgClient.js'>\<\/script>");

    window.RZP.appHost = "{{ config('app.banking_service_url') }}";
    window.RZP.appName = "businessbanking";

    window.rzpTicketSystem = {
      hostname: window.RZP.appHost
    };
  }
</script>

<!-- Hotjar Tracking Code for dashboard.razorpay.com -->
@if(env('APP_ENV') === 'production')
  @include('partials/hotjar')
@endif

@if ($isConfirmed and $isPreSignupComplete)
  <script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
    window.notifications = {!! $notifications !!};
    window.api_host = "{!! $api_host !!}"
  </script>
  <!-- Raven Code -->
  @if(env('APP_ENV') === 'production')
    <script src="{{$cdnDashboardUrl}}/dist/raven-entry.js"></script>
  @endif
  <script src="{{$cdnDashboardUrl}}/dist/merchant-entry.js"></script>
@else
  <script src='{{$cdnDashboardUrl}}/js/generated/signup.js'></script>
  @if(!$isOrgHDFC)
      <script>
        function addHelpNinja() {
          var helpNinjaScript = document.createElement('script');
          helpNinjaScript.setAttribute('src','https://static.helpninja.com/helpninja.js');
          helpNinjaScript.setAttribute('id', 'oc_script');
          helpNinjaScript.setAttribute('convid', '-Kvx6dgy972KCFPlQR0s');
          helpNinjaScript.async = true;

          document.head.appendChild(helpNinjaScript);
        }

        var screenWidth = window.innerWidth;
        if (screenWidth > 780) {
            addHelpNinja();
        }
      </script>
  @endif
@endif


@include('partials/footer')
