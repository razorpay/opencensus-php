
<!DOCTYPE html>
<html lang="en">
    <head>
        <title> {{$data['invoice']['merchant_label']}} </title>

        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />
        <link rel="stylesheet" href="{{env('AWS_CF_CDN_URL')}}/static/auth-link-v2/bundle.css" />
    </head>
<body>
    <div id="authlink-container">
    </div>
    <script type="text/javascript">
        var data = {!! utf8_json_encode($data) !!};
        var Razorpay = {
            config: {
                api: "{{env('APP_URL')}}/"
            }
        };

        function renderAuthLinkPage() {
            window.RZP.renderApp('authlink-container', data);
        }
    </script>
    <script type="text/javascript" src="https://cdn.razorpay.com/static/analytics/bundle.js"></script>
    <script type="text/javascript" src="https://cdn.razorpay.com/static/assets/color.js"></script>
    <script type="text/javascript" src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script type="text/javascript" src="{{env('AWS_CF_CDN_URL')}}/static/auth-link-v2/bundle.js" onload="renderAuthLinkPage()"></script>
</body>
</html>
