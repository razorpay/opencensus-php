<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="google" value="notranslate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Razorpay Browser Extension">
  <meta name="author" content="Razorpay">
  <link rel="shortcut icon" href="/img/favicon.png">
  <title>Razorpay - Browser Extension</title>
  <meta name="description" content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
  <script>
    function renderApp() {
        window.RZP.renderApp('ext-root', {});
    }

    function initAnalytics() {
      var useAnalytics = true;

      if (String.prototype.indexOf && window.rzp_user && window.rzp_user.email && window.rzp_user.email.toLowerCase().indexOf('@razorpay.com') > 0) {
        useAnalytics = false;
      }

      if (window.location.hostname=="dashboard.razorpay.com" && window.analytics && useAnalytics) {
        window.analytics.init(['ga'], {
          ga: 'UA-53341507-2'
        });
      }
    }

  </script>
</head>
<body>
  <div id="ext-root"></div>
  <script id="rzp_analytics" src="https://cdn.razorpay.com/static/analytics/bundle.js" defer onload="initAnalytics()"></script>
  <script src="{{$cdnUrl}}/static/extension/app.js" async defer onload="renderApp()"></script>

  <!-- Hotjar Tracking Code for dashboard.razorpay.com -->
  @include('partials/hotjar')
</body>
