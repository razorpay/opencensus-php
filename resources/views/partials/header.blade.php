<!DOCTYPE html>
<html lang="en" data-ng-app="app">
<head>
  <meta charset="utf-8">
  <meta name="google" value="notranslate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Razorpay Merchant Dashboard">
  <meta name="author" content="Razorpay">
  <link rel="shortcut icon" href="/img/favicon.png">
  <title>Razorpay - Admin Panel</title>
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
