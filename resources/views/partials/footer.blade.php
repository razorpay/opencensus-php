  <script src='{{$cdnBaseUrl}}/static/analytics/bundle.js'></script>
  <script src="https://cdn.razorpay.com/static/assets/holidays.js"></script>
  <script type="text/javascript">

    var noop = function(){};
    //Empty Interface for rzpQ
    window.rzpQ = {
      component: noop, //Track components
      initiated: noop, //User starts an activity
      dropped: noop, //User drops an activity
      success: noop, //Successfully completes activity
      failed: noop, //A failure occured
      push: noop, //Explicitly push as custom event to the queue
      setUser:noop, //Set a user one time
      interaction: noop,
      defineEventModifiers:noop,//Extends to set custom event properties
      //Any modifiers
      onbr: function() {
        return window.rzpQ;
      },
      merchantActions: function() {
        return window.rzpQ;
      },
      productOnboarding: function() {
        return window.rzpQ;
      },
      routeActions: function() {
        return window.rzpQ;
      },
      paymentPages: function() {
        return window.rzpQ;
      },
      paymentLinks: function() {
        return window.rzpQ;
      },
      reporting: function() {
        return window.rzpQ;
      },
      chargeAtWill: function() {
        return window.rzpQ;
      },
      invoice: function() {
        return window.rzpQ;
      },
      now: function() {
        return window.rzpQ;
      },
      paymentButtons: function() {
        return window.rzpQ;
      },
      smartCollect: function() {
        return window.rzpQ;
      },
      subscription: function() {
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

    if (window.analytics) {
        let trackers = ['perf', 'ga', 'fb', 'twitter', 'twitterAgency', 'linkedin', 'bing', 'lj', 'quora', 'reddit']
         if(window.loadHubspot){
            trackers.push('hubspot');
         }

        analytics.init(
          trackers,
          {
            ga: appEnvironment === 'prod' ? 'UA-53341507-2' : 'UA-53341507-4',
            fb: '697927486977350',
            lj:'{{$ljKey}}',
          },
          isLocal,
          appEnvironment,
          disableEventEmitters,
          { appName: 'pg-dashboard' }
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
          'merchantActions':[
            { propertyName:'event_type',
                value:'merchant_dashboard'
            },
            {
                propertyName:'event_group',
                value:'merchant_actions'
            }
          ],
          'routeActions': [
            {
              propertyName:'event_type',
              value:'route'
            },
            {
              propertyName:'event_group',
              value:'route'
            }
          ],
          reporting: [
            { propertyName: 'event_type', value: 'reporting-events' },
            { propertyName: 'event_group', value: 'reporting_events' },
          ],
          paymentPages: [
            {
              propertyName: 'event_type',
              value: 'paymentpages'
            },
            {
              propertyName: 'event_group',
              value: 'paymentpages-dashboard'
            },
          ],
          'paymentLinks':[
              {
                  propertyName:'event_type',
                  value:'paymentlinks'
              },
              {
                  propertyName:'event_group',
                  value:'paymentlink-dashboard'
              },
          ],
          chargeAtWill: [
            {
              propertyName: 'event_type',
              value: 'charge_at_will'
            },
            {
              propertyName: 'event_group',
              value: 'charge_at_will_dashboard'
            },
          ],
          invoice: [
            {
              propertyName: 'event_type',
              value: 'invoice'
            },
            {
              propertyName: 'event_group',
              value: 'invoice_dashboard'
            },
          ],
          paymentButtons: [
            {
              propertyName: 'event_type',
              value: 'paymentbuttons'
            },
            {
              propertyName: 'event_group',
              value: 'paymentbuttons-dashboard'
            }
          ],
          smartCollect: [
            {
              propertyName: 'event_type',
              value: 'smartcollect'
            },
            {
              propertyName: 'event_group',
              value: 'smartcollect-dashboard'
            }
          ],
          subscription: [
            {
              propertyName: 'event_type',
              value: 'subscription'
            },
            {
              propertyName: 'event_group',
              value: 'subscription-dashboard'
            }
          ]
        });


        function getFilteredURLQueryParams(url){
            var search = url.split('?')[1];
            var allParamsWithValues = {};
            var privateParams = ['token','email','invitation'];

            if (search) {
                allParamsWithValues = search.split('&').reduce(function(prev, curr){
                        var [key, value] = curr.split('=');
                        prev[key] = value;
                        return prev;
                    }, {});
            }

            var allParams = Object.keys(allParamsWithValues);
            var filteredParams = allParams.filter(function(param){
                return privateParams.indexOf(param) === -1;
            });

            var filteredParamsWithValues = {};
            filteredParams.forEach(param => filteredParamsWithValues[param] = allParamsWithValues[param]);

            return filteredParamsWithValues;
        };

        function getPathWithoutPrivateData() {
            var fullPath = location.pathname + location.hash + location.search;
            var filteredQueryParamsWithValues = getFilteredURLQueryParams(fullPath);
            var filteredQueryParams = Object.keys(filteredQueryParamsWithValues);
            var filteredURL = fullPath.split('?')[0];

            if(filteredQueryParams.length) {
                filteredURL =  Object.keys(filteredQueryParamsWithValues).reduce(function(path,curParam,index){
                    if(index !== Object.keys(filteredQueryParamsWithValues).length - 1) {
                        return path + '&' + curParam + '=' + filteredQueryParamsWithValues[curParam];
                    }
                    return path + curParam + '=' + filteredQueryParamsWithValues[curParam];
                },filteredURL + '?');
            }

            return filteredURL;
        }

        ga('create', 'UA-53341507-1', 'auto', 'old');

        var path = getPathWithoutPrivateData();

        ga('set', 'page', path);
        ga('old.set', 'page', path);

        window.addEventListener('load', function() {
            analytics.track('ga', 'pageview');
            analytics.track('reddit', 'PageVisit');
            analytics.track('quora', 'ViewContent');
            analytics.track('bing', {action: 'pageLoad', path: path});
        });

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
