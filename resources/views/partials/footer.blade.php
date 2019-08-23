  <!-- <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script> -->
  <script src="http://127.0.0.1:5500/public/static/analytics/bundle.js"></script>
  <script src="https://cdn.razorpay.com/static/assets/holidays.js"></script>
  <script type="text/javascript">
    const noop = ()=>{}
    //Empty Interface for rzpQ
    window.rzpQ = {
                rzpQ: {
                    component: noop, //Track components
                    initiated: noop, //User starts an activity
                    dropped: noop, //User drops an activity
                    success: noop, //Successfully completes activity
                    failed: noop, //A failure occured
                    push: noop, //Explicitly push as custom event to the queue
                    setUser:noop, //Set a user one time
                }
                };
    var useAnalytics = true;
    // if (String.prototype.indexOf && window.rzp_user && window.rzp_user.email && window.rzp_user.email.toLowerCase().indexOf('@razorpay.com') > 0) {
    //     useAnalytics = false;
    // }
    if(true){
    // if (window.location.hostname=="dashboard.razorpay.com" && window.analytics && useAnalytics) {
         analytics.init(['ga', 'fb', 'twitter', 'linkedin', 'bing','lj','perf'], {
           ga: 'UA-53341507-2',
           fb: '697927486977350',
           lj:'10pYUm55sa39zgTN1gzNwQzNyQjM54Cg',
           perf:'medash'
         });
        // Init old key as well
        if(undefined!==analytics.createQ){
            window.rzpQ=analytics.createQ({pollFreq:5000});
        }


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
  </script>
</body>
</html>
