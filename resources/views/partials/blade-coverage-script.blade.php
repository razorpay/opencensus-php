@if(env('APP_ENV') === 'production')
<script async>
  let script = document.createElement('script');
  script.src = 'https://app-metrics.razorpay.com/static/blade-analytics.js';
  script.async = true;
  document.body.append(script);
  script.onload = () => {
    initBladeCoverageAnalytics({ businessUnit: 'merchant-dashboard' });
  };
</script>
@endif
