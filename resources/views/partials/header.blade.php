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
        _rzpAQ = []; // queue for ga
        _rzpAQ_fbq = []; // queue for facebook pixel

        function clearQueue(queue) {
            queue.forEach(function(i) {
                window.rzpAnalytics(queue[i]);
            });
        }

        function emptyRzpAQ () {
            if (typeof ga === 'undefined' ||  _rzpAQ.length) return;
           
            clearQueue(_rzpAQ);

            _rzpAQ = [];
        }

        function emptyRzpAQ_fbq () {
            if (typeof analytics === 'undefined' || _rzpAQ_fbq.length) return;

            clearQueue(_rzpAQ_fbq);

            _rzpAQ_fbq = [];
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

            clearInterval(_qChckr);
            emptyRzpAQ();

            clearInterval(_fbqChckr);
            emptyRzpAQ_fbq();

            switch (data.name) {
                case 'set_dimensions': { // Set the dimensions
                    // If ga is undefined, push to queue
                    if (typeof ga === 'undefined') {
                        _rzpAQ.push(data);
                        return;
                    };

                    ga('old.set', data.dimensions);
                    ga('set', data.dimensions);

                    break;
                }
                case 'facebook': {
                    // If analytics is undefined, push to queue
                    if (typeof analytics === 'undefined') {
                        _rzpAQ_fbq.push(data);
                        return;
                    };
                    
                    analytics.track('fb', data.event, data.value);

                   break;
                }
                default:
                    // If ga is undefined, push to queue
                    if (typeof ga === 'undefined') {
                        _rzpAQ.push(data);
                        return;
                    };

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
