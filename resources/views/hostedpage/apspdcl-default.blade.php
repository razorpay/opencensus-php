<?php
    $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';

    $payment_page_data          = $data['payment_link'] ?? null;
    $is_test_mode               = $data['is_test_mode'] ?? false;
    $has_udf                    = (empty($udf_schema) === false);
    $max_mobile_width           = 853;
?>


<!doctype html>
<html lang="en">
    <head>
        <title>Payment Page - {{$payment_page_data['title']}}</title>
        <meta charset="utf-8">
        <meta name="robots" content="noindex">
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

        @if (isset($payment_page_data))
            <meta property="og:title" content="Payment request by {{$data['merchant']['name']}} for {{$payment_page_data['title']}}">
            <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://razorpay.com/favicon.png'}}">
            <meta property="og:image:width" content="276px">
            <meta property="og:image:height" content="276px">
            <meta property="og:description" content="Click on this link to pay to {{$data['merchant']['name']}}">
        @endif

        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

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

            var templateData = {
                is_test_mode: data.is_test_mode || false,
                payment_page_data: data.payment_link || {},
                max_mobile_width: 853,
                meta: {
//                    company_contact: {
//                        phone: '1800 103 6354',
//                        email: 'asdasd@asdad.com'
//                    },
                    intro_note: 'You can now pay your bill in a simple, convenient and secure way with Razorpay in 3 simple steps:',
                    instructions: ['Enter your Service number.', 'View your Bill details.', 'Pay with your desired mode of payment.']
                },
            };

            templateData = Object.assign({}, data, templateData);

            function renderPaymentPage() {
                window.RZP.renderApp(templateData);
            }
        </script>

        <script src="https://cdn.razorpay.com/static/analytics/bundle.js" async defer></script>
        <script src="http://127.0.0.1:7999/static/hosted/paymentpage_app.js" onload="renderPaymentPage()" defer></script>

        <script src="https://checkout.razorpay.com/v1/checkout.js" async defer></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    </head>

    <body>
        <div id="hostedpage-container">
        </div>
    </body>
</html>
