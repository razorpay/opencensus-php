@include('partials/head')

@if ($isConfirmed and $isPreSignupComplete)
  <% for (var css in htmlWebpackPlugin.files.css) { %>
    <link href="{{$cdnDashboardUrl}}<%= htmlWebpackPlugin.files.css[css] %>" rel="stylesheet">
  <% } %>
@else
  <link rel='stylesheet' href="{{$cdnDashboardUrl}}/css/generated{{asset('style.css')}}" type='text/css' />
@endif

@include('partials/common')

@if ($isConfirmed and $isPreSignupComplete)
  <script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
  </script>
  <% for (var chunk in htmlWebpackPlugin.files.chunks) { %>
  <script src="{{$cdnDashboardUrl}}<%= htmlWebpackPlugin.files.chunks[chunk].entry %>"></script>
  <% } %>
  <!-- smooch code -->
  @if(env('APP_ENV') === 'production')
    <script src='https://cdn.smooch.io/smooch.min.js'></script>
    <script>
      window.smoochScript = $.getScript('https://cdn.smooch.io/smooch.min.js', function() {
        Smooch
          .init({appToken: '02o6kuyoscqkwiqr3ld3lbehw'})
          .then(function () {
              Smooch._rzpReady = true; // custom prop
          });
      })
    </script>
  @endif
@else
  <!-- jQuery & angular -->
  <script src='{{$cdnDashboardUrl}}{{asset('js/generated/pre.js')}}'></script>
  <!-- Merchant Js-->
  <script src='{{$cdnDashboardUrl}}{{asset('js/generated/merchant.js')}}'></script>
  <script async="true" src="https://static.helpninja.com/helpninja.js" id="oc_script" convid="-Kvx6dgy972KCFPlQR0s"></script>
@endif

@include('partials/footer')

<!-- Hotjar Tracking Code for dashboard.razorpay.com -->
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
