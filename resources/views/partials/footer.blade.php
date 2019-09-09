  <!-- <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script> -->
  <script src="http://127.0.0.1:5500/public/static/analytics/bundle.js"></script>
  <script src="https://cdn.razorpay.com/static/assets/holidays.js"></script>
  <script type="text/javascript">
    const noop = ()=>{}
    //Empty Interface for rzpQ
    window.rzpQ = {
                    component: noop, //Track components
                    initiated: noop, //User starts an activity
                    dropped: noop, //User drops an activity
                    success: noop, //Successfully completes activity
                    failed: noop, //A failure occured
                    push: noop, //Explicitly push as custom event to the queue
                    setUser:noop, //Set a user one time
                    defineEventModifiers:noop,//Extends to set custom event properties
                    //Any modifiers
                    onbr:()=>window.rzpQ,
                };
    //Above code doesn't perform any function, can avoid application breakage if the library is
    //removed, not loaded or library code breaks anytime.
    var isLocal = undefined; //Maintaining this for legacy reason, shall clear soon.
    var disableEventEmitters = false; //If true events will not be emitted to LJ and PROM
    var appEnvironment = window.location.hostname=="dashboard.razorpay.com" ? 'prod' : 'stage';
    if(analytics){
        analytics.init(['ga', 'fb', 'twitter', 'linkedin', 'bing','lj','perf'], {
           ga: 'UA-53341507-2',
           fb: '697927486977350',
           lj:'10pYUm55sa39zgTN1gzNwQzNyQjM54Cg',
           perf:'medash'
         },isLocal,appEnvironment,disableEventEmitters);
        // Init old key as well
        if(undefined!==analytics.createQ){
            window.rzpQ=analytics.createQ({pollFreq:500});
        }
        window.rzpQ.defineEventModifiers({
            'onbr':[{propertyName:'event_type',value:'onboarding-events'},{propertyName:'event_group',value:'onboarding'}],
        })

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
