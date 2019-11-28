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
