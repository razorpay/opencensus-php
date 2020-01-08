<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Payout Link</title>
    <meta
            name="viewport"
            content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0"
    />
    <script type="text/javascript">
        _rzpAQ = [];
        function emptyRzpAQ() {
            if (typeof ga === 'undefined' || (_rzpAQ && _rzpAQ.length === 0))
                return;
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
        window.rzpAnalytics = function(data) {
            // If there's no data, don't track anything
            if (!data) return;
            // If ga is undefined, push to queue
            if (typeof ga === 'undefined') {
                _rzpAQ.push(data);
                return;
            }
            // `ga` exists now, empty the queue.
            clearInterval(_qChckr);
            emptyRzpAQ();
            switch (data.name) {
                case 'set_dimensions': // Set the dimensions
                    ga('old.set', data.dimensions);
                    ga('set', data.dimensions);
                    break;
                default:
                    ga(
                        'old.send',
                        'event',
                        data.eventCategory || undefined,
                        data.eventAction || undefined,
                        data.eventLabel || undefined,
                        data.eventValue || undefined,
                    );
                    ga(
                        'send',
                        'event',
                        data.eventCategory || undefined,
                        data.eventAction || undefined,
                        data.eventLabel || undefined,
                        data.eventValue || undefined,
                    );
            }
        };
    </script>
</head>
<body>
<div id="app"></div>
<link
        rel="stylesheet"
        type="text/css"
        href="{{ $banking_url }}/dist/payoutlinks.css"
/>
<!-- <link rel="stylesheet" type="text/css" href="fonts/i.css" /> -->
<script>
    window.data = {
        primary_color: '{{ $primary_color }}',
        logo: '{{ $merchant_logo_url }}',
        client: '{{ $merchant_name }}',
        amount: '{{ $amount }}',
        userDetails: {
            name: '{{ $user_name }}',
            maskedPhone: '{{ $user_phone }}',
            maskedEmail: '{{ $user_email }}',
        },
        description: '{{ $description }}',
        receipt: '{{ $receipt }}',
        apiHost: '{{ $api_host }}' + '/v1/',
        payoutLinkId: '{{ $payout_link_id }}',
        status: '{{ $payout_link_status }}',
        allowUpi : !!'{{ $allow_upi }}',
        fundAccountDetails : JSON.parse('{!! $fund_account_details !!}')
    };
</script>
<script src="{{ $banking_url }}/dist/payoutlinks.js"></script>

@if($is_production)
    <script src="{{ $banking_url }}/dist/raven.js" defer></script>
@endif
</body>
</html>
