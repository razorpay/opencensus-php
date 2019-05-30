<!DOCTYPE html>
<html lang="en" data-ng-app="app">
<head>
  <meta charset="utf-8">
  <meta name="google" value="notranslate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Razorpay Dashboard">
  <meta name="author" content="Razorpay">
  <link rel="shortcut icon" href="/img/favicon.png">
  <title>Razorpay Dashboard</title>
  <meta name="description" content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <script src="https://wchat.freshchat.com/js/widget.js" async defer></script>
  <script src="https://cdn.razorpay.com/static/ticket-system/bundle.js" async defer></script>
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
            if (typeof analytics === 'undefined' || !_rzpAQ_fbq.length) return;

            var q = [].concat(_rzpAQ_fbq);
            _rzpAQ_fbq = [];
            clearQueue(_rzpAQ_fbq);
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
           if (typeof analytics === 'undefined') {
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
         * Method to track Google Analytics
         * @param {Object} eventData Data of the event
         */
        window.rzpAnalytics = function (data) {
            // If there's no data, don't track anything
            if (!data) return;

            switch (data.name) {
                case 'set_dimensions': { // Set the dimensions
                    if (!checkGa(data)) return;

                    ga('old.set', data.dimensions);
                    ga('set', data.dimensions);

                    break;
                }
                case 'facebook': {
                    if (!checkAnalytics(data)) return;

                    analytics.track('fb', data.event, data.value);

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
                    )
                    ga('send',
                        'event',
                        data.eventCategory || undefined,
                        data.eventAction || undefined,
                        data.eventLabel || undefined,
                        data.eventValue || undefined
                    )

                    // Sending Ga events to hubspot
                    var hsqData = {
                        id: data.eventCategory + "__" + data.eventAction,
                    };

                    if (data.eventLabel) {
                        hsqData.value = data.eventLabel;
                        if (data.eventValue) {
                            hsqData.value = data.eventLabel + "__" + data.eventValue;
                        }
                    };
                    window.trackHubs(hsqData);
                }
            }
        }

        // Hubspot trackers
        window.trackHubs = function(data) {
            switch(data.name) {
                case 'identify': {
                    _hsq.push(['identify', {
                        id: data.id, // merchant id
                        email: data.email, // email
                    }]);
                    break;
                }
                case 'create_contact': {
                    _hsq.push(['identify', {
                        email: data.data.email,
                        signup_start: true
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
    <script type="text/javascript">
        var origOpen = XMLHttpRequest.prototype.open;
        var origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function (a, b, c, d, e) {
            if (b.indexOf('hotjar') >= 0) {
                this.send = function (s) {
                    try {
                        var x = JSON.parse(s);

                        // Add MID, email, and contact to the message.
                        if (x['response'] && typeof x['response']['message'] !== 'undefined') {
                            if (x['response']['message'] === null) {
                                x['response']['message'] = '';
                            }

                            if (typeof x['response']['message'] === 'string') {
                                x['response']['message'] += '\n\nMID: ' + rzp_user.id;
                                x['response']['message'] += '\nEmail: ' + ((rzp_user.user && rzp_user.user.email) || rzp_user.email);
                                x['response']['message'] += '\nContact: ' + ((rzp_user.user && rzp_user.user.contact_mobile) || rzp_user.contact_mobile);
                                x['response']['message'] += '\nName: ' + rzp_user.business_name;
                            }
                        }

                        try {
                            s = JSON.stringify(x);
                        } catch (stringifyErr) {}

                        if (x['action'] && (x['action'] === 'create_poll_response' || x['action'] === 'update_poll_response')) {
                            if (x['response_content']) {
                                if (typeof x['response_content'] === 'string') {
                                    try {
                                        var rc = JSON.parse(x['response_content']);
                                        if (rc['answers'] && rc['answers'].length) {
                                            var d = {
                                                mid: window.rzp_user.current,
                                                uid: window.rzp_user.user.id,
                                                feedback: null,
                                                rating: null
                                            };
                                            var a = rc['answers'][0];
                                            if (a['question'] === 'How would you rate the new dashboard home page?') {
                                                d.rating = parseInt(a.answer);
                                            }
                                            if (rc['answers'].length > 1) {
                                                a = rc['answers'][1];
                                                if (a['question'] === 'Please suggest how we can make it better.') {
                                                d.feedback = a.answer;
                                                }
                                            }
                                            var xhr = new XMLHttpRequest();
                                            xhr.open('POST', 'https://hooks.zapier.com/hooks/catch/1088429/zk9ygu/', true);
                                            xhr.send(JSON.stringify(d));
                                        }
                                    } catch (e) {}
                                }
                            }
                        }
                    } catch (e) {}
                    origSend.apply(this, [s]);
                }
            }
            origOpen.apply(this, [a, b, c, d, e]);
        }

    </script>
