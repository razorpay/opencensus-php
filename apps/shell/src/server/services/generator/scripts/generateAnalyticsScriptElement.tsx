import React from 'react';

export const generateAnalyticsScriptElement = () => {
  return (
    <script
      key="analytics-script"
      dangerouslySetInnerHTML={{
        __html: `
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

                // Handle Lumberjack Events for RazorpayX
                if (data.ljEventName && window.rzpQ) {
                    if (!checkGa(data)) return;

                    var ljEventType = data.ljEventType;
                    var ljEventName = data.ljEventName;
                    var ljProperties = data.ljProperties;
                    var ljEventTypes = ['initiated', 'success', 'dropped', 'failed', 'viewed'];
                    var eventType = ljEventTypes.indexOf(ljEventType) === -1 ? 'interaction' : ljEventType;

                    window.rzpQ.merchantActions()[eventType](ljEventName);
                    window.rzpQ.push(ljProperties);
                }

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
        `,
      }}
    />
  );
};
