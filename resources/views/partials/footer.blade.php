<script src='{{$cdnBaseUrl}}/static/analytics/bundle.js'></script>
  <script src="https://cdn.razorpay.com/static/assets/holidays.js"></script>
  <script type="text/javascript">
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


    if (window.razorpayAnalytics) {
        let trackers = ['lj'];

         if (window.location.href.indexOf('resetpassword') === -1) {
          trackers = ['perf', 'ga', 'fb', 'twitter', 'bing', 'quora', 'reddit'];
          if(window.loadHubspot){
            trackers.push('hubspot');
          }
           trackers.push('twitterAgency');
         }

         razorpayAnalytics.init(
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
        if(razorpayAnalytics.createQ){
          window.rzpQ = razorpayAnalytics.createQ({ pollFreq:500 });
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
          paymentStores: [
            {
              propertyName: 'event_type',
              value: 'paymentstores'
            },
            {
              propertyName: 'event_group',
              value: 'paymentstores-dashboard'
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
          subscriptionButtons: [
            {
              propertyName: 'event_type',
              value: 'subscriptionbuttons'
            },
            {
              propertyName: 'event_group',
              value: 'subscriptionbuttons-dashboard'
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
          ],
          qrCode: [
            {
              propertyName: 'event_type',
              value: 'qrcode'
            },
            {
              propertyName: 'event_group',
              value: 'qrcode-dashboard'
            }
          ],
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

        if (window.location.href.indexOf('resetpassword') === -1) {
          ga('create', 'UA-53341507-1', 'auto', 'old');

          var path = getPathWithoutPrivateData();

          ga('set', 'page', path);
          ga('old.set', 'page', path);

          window.addEventListener('load', function() {
              razorpayAnalytics.track('ga', 'pageview');
              razorpayAnalytics.track('reddit', 'PageVisit');
              razorpayAnalytics.track('quora', 'ViewContent');
              razorpayAnalytics.track('bing', {action: 'pageLoad', path: path});
          });
        }

        try {
          var pendingAction = JSON.parse(razorpayAnalytics.utils.getCookie('pendingAction'));
          if (pendingAction && pendingAction.type === 'signup-form') {
            razorpayAnalytics.track('fb', 'CompleteRegistration');
            razorpayAnalytics.utils.deleteCookie('pendingAction');
          }

          var rzpUTM = JSON.parse(razorpayAnalytics.utils.getCookie('rzp_utm'));
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

    if (window.isAuthPage && window.location.href.indexOf('resetpassword') === -1) {
      // Due to some reasons this code is failing, due to this Login form failed to load. By adding the try catch we stopped breaking the UI.
      // https://razorpay.slack.com/archives/C2309Q91T/p1672906249200659
      try {
        !function(){
          var analytics= window.analytics = window.analytics||[];

          if(!analytics.initialize) {
            if (analytics.invoked) {
              window.console&&console.error&&console.error("Segment snippet included twice.");
            } else {
              analytics.invoked=!0;
              analytics.methods=["trackSubmit","trackClick","trackLink","trackForm","pageview","identify","reset","group","track","ready","alias","debug","page","once","off","on","addSourceMiddleware","addIntegrationMiddleware","setAnonymousId","addDestinationMiddleware"];

              analytics.factory= function(t){
              return function(){
                var e=Array.prototype.slice.call(arguments);e.unshift(t);

                // see https://github.com/segmentio/analytics.js/issues/253#issuecomment-24280169
                if (!window.analytics.push) {
                  window.analytics.push = Array.prototype.push.bind(window.analytics);
                }

                analytics.push(e);

                return analytics
              }
            };

            for(var t=0;t<analytics.methods.length;t++){
              var e=analytics.methods[t];
              analytics[e]=analytics.factory(e)
            }

            analytics.load= function(t,e){
              var n=document.createElement("script");
              n.type="text/javascript";
              n.async=!0;
              n.src="https://cdn.segment.com/analytics.js/v1/"+t+"/analytics.min.js";
              var a=document.getElementsByTagName("script")[0];
              a.parentNode.insertBefore(n,a);
              analytics._loadOptions=e
            }

            analytics.SNIPPET_VERSION="4.1.0";

            // Events on signup and signin are required to be sent to Website project(Segment).
            // isAuthPage is set to true only by signup/signin/forgot password/2FA pages. (excludes all /app pages).
            // if isAuthPage is true, send to website project or else dashboard project.

            //when X loads the dashboard for activation in an iframe, we pass merchant=x in queryParams
            //if current URL matches X params, send events to x-website project(Segment)
            if (window.location.href.includes('merchant=x')) {
                analytics.load(window.X_WEBSITE_SEGMENT_API_KEY);
            } else {
                analytics.load(window.WEBSITE_SEGMENT_API_KEY);
            }
            analytics.page();
          }
        }
      }();
      } catch(error) {
        console.warn(error);
      }
    }
  </script>
</body>
</html>
