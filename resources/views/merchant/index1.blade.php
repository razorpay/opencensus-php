
@include('partials/header')

@if ($requestPath === 'signup')
  <title>Create your Razorpay Account - Razorpay</title>
  <meta name="description" content="Welcome to Razorpay! Create your free Razorpay account today. Sign up for free to join the millions of users that trust us with their payments, banking & working capital." />
@else
  <title>Razorpay Dashboard</title>
  <meta name="description" content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard" />
@endif

<script>
  var _dcq = _dcq || [];
  var _dcs = _dcs || {};
  _dcs.account = '9421167';

  (function() {
    var dc = document.createElement('script');
    dc.type = 'text/javascript'; dc.async = true;
    dc.src = '//tag.getdrip.com/9421167.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(dc, s);
  })();
</script>


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

@if(env('APP_ENV') === 'production')
  <script type="module">
    import {Workbox} from 'https://storage.googleapis.com/workbox-cdn/releases/6.4.1/workbox-window.prod.mjs';
      if ('serviceWorker' in navigator) {
        const wb = new Workbox('/sw-merchant.js');
        wb.register();
      }
  </script>
@endif

<!-- Hotjar Tracking Code for dashboard.razorpay.com -->
@if(env('APP_ENV') === 'production')
  <link rel="dns-prefetch" href="https://static.hotjar.com">
  <link rel="dns-prefetch" href="https://vars.hotjar.com">
  <link rel="dns-prefetch" href="https://script.hotjar.com">
  @include('partials/hotjar')
@endif
@include('partials/preload/merchant-preload')

<!-- Blank interface init before loading the project entry file -->
@include('partials/rzpq-interface')

<!-- if logged in and not on website -->
@if (($isConfirmed || $isMobileConfirmed) and $isPreSignupComplete and (app('request')->input('auth_source') !== 'website' and app('request')->input('auth_source') !== 'website_homepage'))
  <!-- Preconnect to required domains  -->
  <script
    defer
    src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"
    crossorigin="anonymous"
    integrity="sha512-oJp0DdQuQQrRsKVly+Ww6fAN1GwJN7d1bi8UubpEbzDUh84WrJ2CFPBnT4LqBCcfqTcHR5OGXFFhaPe3g1/bzQ=="
  ></script>
  <link rel="preload" href="{{$cdnDashboardUrl}}/dist/merchant-entry.js" as="script">
  <link rel="dns-prefetch" href="https://rzp-1415-prod-dashboard-activation.s3.amazonaws.com">
  <link rel="dns-prefetch" href="https://maxcdn.bootstrapcdn.com">
  <link rel="dns-prefetch" href="https://o515678.ingest.sentry.io">
  <link rel="dns-prefetch" href="https://www.google-analytics.com">
  <link rel="dns-prefetch" href="https://www.googleadservices.com">
  <link rel="dns-prefetch" href="https://connect.facebook.net">
  <link rel="dns-prefetch" href="https://www.youtube.com">
  <link rel="dns-prefetch" href="https://googleads.g.doubleclick.net">
  <link rel="dns-prefetch" href="https://www.facebook.com">
  <link rel="dns-prefetch" href="https://www.google.com">
  <link rel="dns-prefetch" href="https://www.google.co.in">
  <link rel="dns-prefetch" href="https://api.refiner.io">
  <link rel="dns-prefetch" href="https://js.refiner.io">
  <link rel="dns-prefetch" href="https://d2r1yp2w7bby2u.cloudfront.net">
  <link rel="dns-prefetch" href="https://cdn.segment.com">
  <link rel="dns-prefetch" href="https://lumberjack.razorpay.com">
  <!-- preconnect fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://www.gstatic.com">

  <!-- Preload FA icons CSS -->
  <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" as="style">

  <!-- head tag ends here -->
  @include('partials/common')
  <script defer src="https://www.googletagmanager.com/gtag/js?id=AW-928471290"></script>
  <script defer src="https://apis.google.com/js/api:client.js"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'AW-928471290');
  </script>
  
  <script src="https://www.recaptcha.net/recaptcha/api.js?render=explicit"></script>
@endif
