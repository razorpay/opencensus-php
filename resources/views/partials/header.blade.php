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
  <script type="text/javascript">
        _rzpAQ = [];
        function emptyRzpAQ () {
            if (typeof ga === 'undefined' || (_rzpAQ && _rzpAQ.length === 0)) return;
            var q = [].concat(_rzpAQ);
            _rzpAQ = [];
            if (q.length > 0) {
                for (var i = 0; i < q.length; i++) {
                    window.rzpAnalytics(q[i]);
                }
            }
        }
        var _qChckr = setInterval(emptyRzpAQ, 500);
        /**
         * Method to track Google Analytics
         * @param {Object} eventData Data of the event
         */
        window.rzpAnalytics = function (data) {
            // If there's no data, don't track anything
            if (!data) return;

            // If ga is undefined, push to queue
            if (typeof ga === 'undefined') {
                _rzpAQ.push(data);
                return;
            };

            // `ga` exists now, empty the queue.
            clearInterval(_qChckr);
            emptyRzpAQ();

            switch (data.name) {
                case 'set_dimensions': // Set the dimensions
                    ga('set', data.dimensions);
                    break;
                default:
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
                        let x = JSON.parse(s);
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
