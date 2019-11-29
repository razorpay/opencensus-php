  <script src='{{$cdnBaseUrl}}/static/analytics/bundle.js'></script>
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
      productOnboarding: function() {
        return window.rzpQ;
      }
    };

    //Above code doesn't perform any function, can avoid application breakage if the library is
    //removed, not loaded or library code breaks anytime.
    //For any environment where we want to disable tracking other than LJ turn isLocal to true.
    var isLocal = undefined;
    //Maintaining this for legacy reason. Should ideally be defined by the environments.
    //Below peice of code will turn off trackers other than LJ in non-prod environments, since LJ is environment specific we do not have to follow this approach.
    if ('{{$env}}'==='dev' || String.prototype.indexOf && window.rzp_user && window.rzp_user.email && window.rzp_user.email.toLowerCase().indexOf('@razorpay.com') > 0) {
      isLocal = true;
    }

    var disableEventEmitters = '{{$env}}'==='dev' ? true : false; //If true events will not be emitted to LJ and PROM
    var appEnvironment = window.location.hostname == "dashboard.razorpay.com" ? 'prod' : 'stage';

    if(window.analytics){
        analytics.init(
          ['ga', 'fb', 'twitter', 'linkedin', 'bing','lj'],
          {
            ga: 'UA-53341507-2',
            fb: '697927486977350',
            lj:'{{$ljKey}}',
          //  perf:'medash-{{$env}}'
          },
          isLocal,
          appEnvironment,
          disableEventEmitters
        );

         // Init old key as well
        if(analytics.createQ){
          window.rzpQ = analytics.createQ({ pollFreq:500 });
        }

        window.rzpQ.defineEventModifiers({
          'onbr':[
            { propertyName:'event_type',
              value:'onboarding-events'
            },
            {
              propertyName:'event_group',
              value:'onboarding'
          }],
          'productOnboarding':[
            { propertyName:'event_type',
              value:'product-onboarding-events'
            },
            {
              propertyName:'event_group',
              value:'product-onboarding'
          }],
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
