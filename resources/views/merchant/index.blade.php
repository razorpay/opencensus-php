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
    window.api_host = "{!! $api_host !!}"
  </script>
  <!-- Raven Code -->
  @if(env('APP_ENV') === 'production')
    <script src="{{$cdnDashboardUrl}}/dist/raven-entry.js"></script>
  @endif
  <script src="{{$cdnDashboardUrl}}/dist/merchant-entry.js"></script>
@else
  <script src='{{$cdnDashboardUrl}}/js/generated/signup.js'></script>
  <script>
    function addHelpNinja() {
      var helpNinjaScript = document.createElement('script');
      helpNinjaScript.setAttribute('src','https://static.helpninja.com/helpninja.js');
      helpNinjaScript.setAttribute('id', 'oc_script');
      helpNinjaScript.setAttribute('convid', '-Kvx6dgy972KCFPlQR0s');
      helpNinjaScript.async = true;

      document.head.appendChild(helpNinjaScript);
    }

    const screenWidth = window.innerWidth;

    if (screenWidth > 780) {
        addHelpNinja();
    }
  </script>
@endif


@include('partials/footer')
