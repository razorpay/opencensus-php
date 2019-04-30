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

<!-- Hotjar Tracking Code for dashboard.razorpay.com -->
@if(env('APP_ENV') === 'production')
  <script>
    if (location.hostname === 'dashboard.razorpay.com') {
      (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:575141,hjsv:5};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
      })(window,document,'//static.hotjar.com/c/hotjar-','.js?sv=');
    }
  </script>
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
  <script src="https://cdn.razorpay.com/static/assets/currency.js"></script>
@else
  <script src='{{$cdnDashboardUrl}}/js/generated/signup.js'></script>
@endif


@include('partials/footer')
