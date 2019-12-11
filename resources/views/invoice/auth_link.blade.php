
<!DOCTYPE html>
<html lang="en">
<head>

    <title> {{$data['invoice']['merchant_label']}} </title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

    <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />
</head>
<body>
</body>

<script type="text/javascript">
    const data = {!!utf8_json_encode($data)!!};
    var Razorpay = {
        config: {
            api: "{{env('APP_URL')}}/"
        }
    }

    function noop() {}

    window.rzpQ = {
        initiated: noop,
        push: noop,
        now: function() {
            return window.rzpQ;
        },
        defineEventModifiers:noop,
        authLink: function() {
            return window.rzpQ;
        }
    };

    function initAnalytics() {
        if (!window.analytics || window.location.hostname.indexOf('razorpay.com') < 0) {
            return;
        }

        window.analytics.init(['la'], {
            la: '96df432a283745908a06f711acd9e5eb'
        });

        if (window.analytics.createQ) {
            window.rzpQ = window.analytics.createQ({ pollFreq:500 });
        }
    }
</script>
<script type="text/javascript" src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script type="text/javascript" src="{{env('AWS_CF_CDN_URL')}}/static/auth_link/bundle.js"></script>
<script type="text/javascript" onload="initAnalytics()" src='https://cdn.razorpay.com/static/analytics/bundle.js' async></script>
</html>
