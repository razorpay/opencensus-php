<!DOCTYPE html>
<html lang="en" data-ng-app="app">
<head>
  <meta charset="utf-8">
  <meta name="google" value="notranslate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="Razorpay">
  <link rel="shortcut icon" href="/img/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  @include('partials/environment')
  <script type="text/javascript">
    // New signup flow: Redirect rule to pass extra URL params via Google Optimize
    // Input: /signup?utm_expid=exp_id#/access/signup?email=email_id
    // Output: /signup?utm_expid=exp_id&email=email_id
    const regex = /#\/access\/signup\?/g;
    if (location.pathname === "/signup" && location.hash.match(regex)) {
        location.href = location.href.replace(regex, "&")
    } else if (location.pathname === '//signup') {
        // partner referral shortened url is wrongly translating links by adding extra slash in pathname
        // Eg: https://rzp.io/i/TSvxo0WXs ==> https://dashboard.razorpay.com//signup?referral_code=bluehosti2lycr
        // Since already there are huge number of links created and being used we can't just fix the translation from BE
        // as issue will persist for existing links, hence adding a fallback redirect
        location.href = location.href.replace('//signup', "/signup");
    }
  </script>
  @if(env('APP_ENV') === 'production')
    <script src="https://www.googleoptimize.com/optimize.js?id=GTM-NCWFQ39"></script>
  @else
    <script src="https://www.googleoptimize.com/optimize.js?id=GTM-WB43S6Q"></script>
  @endif
  <script src="https://wchat.freshchat.com/js/widget.js" async defer></script>
  <script type="text/javascript">
        var _hsq = window._hsq = window._hsq || [];

        _rzpAQ = []; // queue for ga
        _rzpAQ_fbq = []; // queue for facebook pixel

        function clearQueue(queue) {
            for(var i=0; i < queue.length; i++) {
                window.rzpAnalytics(queue[i]);
            }
        }

        function emptyRzpAQ () {
            if (typeof ga === 'undefined' ||  !_rzpAQ.length) return;

            var q = [].concat(_rzpAQ);
            _rzpAQ = [];
            clearQueue(q);
        }

        function emptyRzpAQ_fbq () {
            if (typeof razorpayAnalytics === 'undefined' || !_rzpAQ_fbq.length) return;

            var q = [].concat(_rzpAQ_fbq);
            _rzpAQ_fbq = [];
            clearQueue(q);
        }

        function checkGa(data) {
             // If ga is undefined, push data to _rzpAQ
            if (typeof ga === 'undefined') {
                _rzpAQ.push(data);
                return false;
            }

            clearInterval(_qChckr);
            emptyRzpAQ();

            return true;
        }

        function checkAnalytics(data) {
           // If analytics is undefined, push to _rzpAQ_fbq
            if (typeof razorpayAnalytics === 'undefined') {
                _rzpAQ_fbq.push(data);
                return false;
            }

            clearInterval(_fbqChckr);
            emptyRzpAQ_fbq();

            return true;
        }


        var _qChckr = setInterval(emptyRzpAQ, 500),
            _fbqChckr = setInterval(emptyRzpAQ_fbq, 500);

        /**
         * Method to track Analytics
         * @param {Object} eventData Data of the event
         */
        window.rzpAnalytics = function (data) {
            // If there's no data, don't track anything
            if (!data) return;
            const eventTypes = { twitterAgency: 'twitterAgency' };
            switch (data.name) {
                case 'set_dimensions': { // Set the dimensions
                    if (!checkGa(data)) return;

                    ga('old.set', data.dimensions);
                    ga('set', data.dimensions);

                    break;
                }
                case 'facebook': {
                    if (!checkAnalytics(data)) return;
                    razorpayAnalytics.track('fb', data.event, data.value);

                    break;
                }
                case 'bing': {
                    if (!checkAnalytics(data)) return;
                    razorpayAnalytics.track('bing', data.event);

                    break;
                }
                case 'twitter': {
                    if (!checkAnalytics(data)) return;
                    razorpayAnalytics.track('twitter', '', data.value);

                    break;
                }
                case eventTypes.twitterAgency : {
                    if (!checkAnalytics(data)) return;
                    razorpayAnalytics.track(eventTypes.twitterAgency, '', data.value);

                    break;
                }
                case 'quora': {
                    if (!checkAnalytics(data)) return;
                    razorpayAnalytics.track('quora', data.event, data.value);
                    break;
                }
                case 'reddit': {
                    if (!checkAnalytics(data)) return;
                    razorpayAnalytics.track('reddit', data.event);
                    break;
                }
                case 'linkedIn': {
                    if (!checkAnalytics(data)) return;
                    razorpayAnalytics.track('linkedin', '', data.value);

                    break;
                }

                default: {
                    if (!checkGa(data)) return;
                    ga('old.send',
                        'event',
                        data.eventCategory || undefined,
                        data.eventAction || undefined,
                        data.eventLabel || undefined,
                        data.eventValue || undefined
                    );

                    ga('send',
                        'event',
                        data.eventCategory || undefined,
                        data.eventAction || undefined,
                        data.eventLabel || undefined,
                        data.eventValue || undefined
                    );
                }
            }
        }

        // Hubspot trackers
        window.trackHubs = function(data) {
            switch(data.name) {
                case 'identify': {
                    _hsq.push(['identify', {
                        mid: data.id, // merchant id
                        email: data.email, // email
                    }]);
                    break;
                }
                case 'create_contact': {
                    if(data.data){
                        _hsq.push(['identify', {
                            email: data.data.email,
                            signup_start: true,
                            ...data.data
                        }]);

                        _hsq.push(['trackEvent', {
                            id: 'CREATING_CONTACT'
                        }]);

                        setTimeout(function() {
                            _hsq.push(['identify', {
                                email: data.data.email,
                                signup_start: true
                            }]);

                            _hsq.push(['trackEvent', {
                                id: 'SIGNUP_START'
                            }]);
                        }, 5000);
                    }

                    break;
                }
                case 'update_property': {
                    _hsq.push(['identify', data.data]);

                    _hsq.push(['trackEvent', {
                        id: 'UPDATE_CONTACT_PROPERTY',
                    }]);
                    break;
                }
                default : {
                    _hsq.push(['trackEvent', {
                        id: data.id,
                        value: data.value
                    }]);
                }
            }
        }
    </script>
@include('partials/xhr_overwrite')
