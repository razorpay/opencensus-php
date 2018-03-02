@include('partials/header')

@if ($isConfirmed and $isPreSignupComplete)

@else
  <link rel='stylesheet' href="{{$cdnDashboardUrl}}/css/generated/signup.css" type='text/css' />
@endif

@include('partials/common')

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
  <script src="{{$cdnDashboardUrl}}/dist/merchant-entry.js"></script>
  <!-- smooch code -->
  @if(env('APP_ENV') === 'production')
    <script src="{{$cdnDashboardUrl}}/dist/raven-entry.js"></script>
    <script src='https://cdn.smooch.io/smooch.min.js'></script>
    <script>
      (function () {
        var script = document.createElement("script");
        script.onload = function() {
          Smooch
            .init({appToken: '02o6kuyoscqkwiqr3ld3lbehw'})
            .then(function () {
                Smooch._rzpReady = true; // custom prop
            });
        };
        script.src = "https://cdn.smooch.io/smooch.min.js";

        document.getElementsByTagName("head")[0].appendChild(script);
      }());
    </script>
  @endif
@else
  <script src='{{$cdnDashboardUrl}}/js/generated/signup.js'></script>
  <script async="true" src="https://static.helpninja.com/helpninja.js" id="oc_script" convid="-Kvx6dgy972KCFPlQR0s"></script>
@endif

@include('partials/footer')
