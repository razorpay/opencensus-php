  <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script>
  <script src="https://cdn.razorpay.com/static/assets/holidays.js"></script>
  <script type="text/javascript">
    var useAnalytics = true;
    if (String.prototype.indexOf && window.rzp_user && window.rzp_user.email && window.rzp_user.email.toLowerCase().indexOf('@razorpay.com') > 0) {
        useAnalytics = false;
    }
    if (true || window.location.hostname=="dashboard.razorpay.com" && window.analytics && useAnalytics) {
        analytics.init(['ga', 'fb', 'linkedin'], {
          ga: 'UA-53341507-2',
          fb: '697927486977350'
        });
        // Init old key as well
        ga('create', 'UA-53341507-1', 'auto', 'old');

        ga('set', 'page', location.pathname + location.hash + location.search);
        ga('old.set', 'page', location.pathname + location.hash + location.search);
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
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '697927486977350');
    fbq('track', 'PageView');
  </script>
</body>
</html>
