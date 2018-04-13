  <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script>
  <script tyoe="text/javascript">
    var useAnalytics = true;
    if (String.prototype.indexOf && window.rzp_user && window.rzp_user.email && window.rzp_user.email.toLowerCase().indexOf('@razorpay.com') > 0) {
        useAnalytics = false;
    }
    if (window.location.hostname=="dashboard.razorpay.com" && window.analytics && useAnalytics) {
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
