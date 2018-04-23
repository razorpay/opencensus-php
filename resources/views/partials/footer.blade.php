  <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script>
  <script type="text/javascript">
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
          var pendingAction = JSON.parse(analytics.utils.getCookie('pendingAction'));
          if (pendingAction && pendingAction.type === 'signup-form') {
            analytics.track('fb', 'CompleteRegistration');
            analytics.utils.deleteCookie('pendingAction');
          }

          var rzpUTM = JSON.parse(analytics.utils.getCookie('rzp_utm'));
          var techSignUp = JSON.parse(localStorage.getItem('track-tech-signup'));
          if (rzpUTM && !techSignUp) {
            var urlTokens =  rzpUTM.website ? rzpUTM.website.split('/') : [];
            for(var i = 0; i < urlTokens.length; i++) {
                if (urlTokens[i] === 'tech') {
                  localStorage.setItem('track-tech-signup', true);

                  window.rzpAnalytics({
                    eventCategory: 'Tech Hiring Page',
                    eventAction: 'Click - Signup'
                  });

                  break;
                }
            }
          }
        } catch(e) {}
    } else {
        ga = function () {};
    }
  </script>
</body>
</html>
