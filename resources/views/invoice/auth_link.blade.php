
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
        interaction: noop,
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

        window.analytics.init(['ga', 'lj'], {
            lj: data.is_test_mode
                ? '96df432a283745908a06f711acd9e5eb' // 'feb51cc8168711ea8d71362b9e155667' Please add this key when data analytics issue fixed
                : '96df432a283745908a06f711acd9e5eb'
        });

        if (window.analytics.createQ) {
            window.rzpQ = window.analytics.createQ({ pollFreq: 500 });
        }

        window.rzpQ.defineEventModifiers({
            'authLink':[
                {
                    propertyName:'event_type',
                    value:'charge_at_will'
                },
                {
                    propertyName:'event_group',
                    value:'charge_at_will_hosted_page'
                },
                {
                    propertyName: 'page_id',
                    value: data.invoice.id
                }
            ]
        });

        window.rzpQ.push(
            window.rzpQ
            .now()
            .authLink()
            .interaction('auth_link.payment.landed')
        );
    }

    window.trackLink = function() {
        window.rzpQ.push(
            window.rzpQ
            .now()
            .authLink()
            .interaction('auth_link.payment.redirect', { type: 'footer' })
        );
    }
</script>
<script type="text/javascript" src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script type="text/javascript" src="{{env('AWS_CF_CDN_URL')}}/static/auth_link/bundle.js"></script>
<script type="text/javascript" onload="initAnalytics()" src='https://cdn.razorpay.com/static/analytics/bundle.js'></script>
</html>
