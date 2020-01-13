<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Payout Link</title>
    <style>
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 400;
            src: local('Muli Regular'), local('Muli-Regular'),
            url(https://fonts.gstatic.com/s/muli/v12/7Auwp_0qiz-afTLGLQjUwkQ.woff2)
            format('woff2'),
            url(/dist/assets/fonts/muli-v12-latin-regular.woff) format('woff')
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA,
            U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215,
            U+FEFF, U+FFFD;
        }
        /* latin */
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 600;
            src: local('Muli SemiBold'), local('Muli-SemiBold'),
            url(https://fonts.gstatic.com/s/muli/v12/7Au_p_0qiz-ade3iOCX2z24PMFk.woff2)
            format('woff2'),
            url(/dist/assets/fonts/muli-v12-latin-600.woff) format('woff');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA,
            U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215,
            U+FEFF, U+FFFD;
        }
        /* latin */
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 700;
            src: local('Muli Bold'), local('Muli-Bold'),
            url(https://fonts.gstatic.com/s/muli/v12/7Au_p_0qiz-adYnjOCX2z24PMFk.woff2)
            format('woff2'),
            url(/dist/assets/fonts/muli-v12-latin-700.woff) format('woff');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA,
            U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215,
            U+FEFF, U+FFFD;
        }
        body {
            margin: 0; padding:0;
        }
        #app {
            width: 100vw;
            height: 100vh;
            background: #e3e3e3;
        }
        #app .loading_container {
            width: 90%;
            margin: 0 auto;
            padding-top: 20vh;
            font-family: "Muli"
        }
        .spinner {
            display: block;
            text-align: center;
            margin: auto;
            position: relative;
        }
        .spinner:before {
            width: 40px;
            height: 40px;
            display: inline-block;
            position: relative;
            text-align: initial;
            border: 8px solid #C4C4C4;
            border-top-color: transparent;
            border-radius: 50%;
            content: "";
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
            -webkit-animation: loader_keyframes .7s infinite linear;
            animation: loader_keyframes .7s infinite linear;
            contain: content;
        }
        #app .loading_container h1{
            text-align: center;
            line-height: 30px;
        }
        @keyframes loader_keyframes {
            from {transform: rotate(0deg)}
            to {transform: rotate(1turn)}
        }
        .poweredBy{
            width: 36px;
            height: 2px;
            background: #3281FF;
            margin: 0 auto;
        }
        .securedFontContainer{
            font-size: 12px;
            line-height: 18px;
            color: #626262;
            display: flex;
            justify-content: center;
            margin-top:50px;
        }
    </style>
    <meta
            name="viewport"
            content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0"
    />
    @if($is_production)
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
    @endif
</head>
<body>
<div id="app">
    <div class="loading_container">
        <div class="spinner"></div>
        <h1>
            Some witty content. Can't think of anything right now.
        </h1>
        <div class="poweredBy"></div>
        <div class="securedFontContainer">
            <span>Secured by </span>
            <img
                    src="https://betacdn.razorpay.com/static/assets/razorpayx/logos/rx-dark-logo.png"
                    style="width: 90px; margin-left: 3px;"
            />
        </div>
    </div>
</div>
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
        fundAccountDetails : JSON.parse('{!! $fund_account_details !!}'),
        purpose  : '{{ $purpose }}'
    };
</script>
<script src="{{ $banking_url }}/dist/payoutlinks.js"></script>
@if($is_production)
    <script src="{{ $banking_url }}/dist/raven.js" defer></script>
@endif
</body>
</html>
