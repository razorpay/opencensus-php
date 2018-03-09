  <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script>
  <script tyoe="text/javascript">
    if (window.location.hostname=="dashboard.razorpay.com" && window.analytics) {
        analytics.init(['ga', 'fb'], {
          ga: 'UA-53341507-2'
        });
        analytics.track('ga', 'pageview');
        try {
          if (JSON.parse(analytics.utils.getCookie('pendingAction')).type === 'signup-form') {
            analytics.track('fb', 'CompleteRegistration');
            analytics.utils.deleteCookie('pendingAction');
          }
        } catch(e) {}
    } else {
        ga = function () {};
    }
  </script>
</body>
</html>
