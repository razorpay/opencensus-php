@include('partials/head')

@if ($isConfirmed and $isPreSignupComplete)
  
@else
  <link rel='stylesheet' href="{{$cdnDashboardUrl}}/css/generated/style.css" type='text/css' />
@endif

@include('partials/common')

@if ($isConfirmed and $isPreSignupComplete)
  <script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
  </script>
  
  <script src="{{$cdnDashboardUrl}}/dist/manifest.js"></script>
  
  <script src="{{$cdnDashboardUrl}}/dist/vendor.js"></script>
  
  <script src="{{$cdnDashboardUrl}}/dist/merchant.js"></script>
  
@else
  <!-- jQuery & angular -->
  <script src='{{$cdnDashboardUrl}}/js/generated/pre.js'></script>
  <!-- Merchant Js-->
  <script src='{{$cdnDashboardUrl}}/js/generated/merchant.js'></script>
@endif

<!-- supportkiy code -->
@if(env('APP_ENV') !== 'testing')
   @include('partials/supportkit')
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
