  <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script>
  <script>
    ga = function () {};
    analytics.init(['fb'], {
      ga_dash: 'UA-53341507-2'
    });
    analytics.track('ga_dash', 'pageview');

    try {
      if (JSON.parse(analytics.utils.getCookie('pendingAction')).type === 'signup-form') {
        analytics.track('fb', 'CompleteRegistration');
        analytics.utils.deleteCookie('pendingAction');
      }
    } catch(e) {}
  </script>
</body>
</html>
