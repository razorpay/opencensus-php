@include('partials/header')

@include('partials/common')

@include('partials/preload/merchantLA-preload')

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

<script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
    window.api_host = "{!! $api_host !!}"
</script>

@include('partials/rzpq-interface')

<script src="{{$cdnDashboardAssetsUrl}}/dashboard/core-bundles/la-dashboard/la-dashboard.entry.js"></script>

@include('partials/blade-coverage-script')
@include('partials/footer')
