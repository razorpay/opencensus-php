<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <meta name="robots" content="noindex, follow" />

    <meta http-equiv="pragma" content="no-cache" />
    <meta
        name="viewport"
        content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1"
    />

    <meta name="description" content="Payment Button powered by Razorpay" />

    <title>Powered By Razorpay</title>

    <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

    <link
        rel="stylesheet"
        href="https://betacdn.razorpay.com/static/widget/payment-form.css"
    />
    <!-- TODO: Lazy load fonts -->
    <link
        href="https://fonts.googleapis.com/css?family=Lato:300,400,700"
        rel="stylesheet"
        type="text/css"
    />

    @if (isset($data['environment']))
        @if ($data['environment'] !== 'production')
        <script>
            var Razorpay = {
                config: {
                    api: "{{ config('app.url') }}/"
                }
            }
        </script>
        @endif
    @endif

    <script>
        var data = {!!utf8_json_encode($data)!!};
    </script>

    <script>
        function loadRazorpayPaymentForm() {
            window.RZP.loadRazorpayPaymentForm(data);
        }

    </script>

    <!-- TODO: Lazy load while button is loaded for faster load. Use this tag only for fallback-->
    <script
        type="text/javascript"
        src="https://cdn.razorpay.com/static/assets/color.js"
        defer
    ></script>

    <script
        type="text/javascript"
        src="https://betacdn.razorpay.com/static/widget/payment-form.js"
        onload="loadRazorpayPaymentForm()"
        defer
    ></script>
</head>
<body></body>
</html>
