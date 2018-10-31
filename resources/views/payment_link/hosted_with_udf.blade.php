<?php
    $payment_page_data          = $data['payment_link'];
    $is_test_mode               = $data['is_test_mode'] ?? false;
    $has_udf                    = (empty($udf_schema) === false);
    $meta_description           = $payment_page_data['description']? $payment_page_data['description'] : 'Payment request by '. $data['merchant']['name'];
    $dark_theme_color           = '#383838';
    $light_theme_color          = '#efefef';
?>


<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
        @include('hostedpage.partials.robot')
        <meta name="description" content="{{$meta_description}}">

        <meta property="og:title" content="Payment request by {{$data['merchant']['name']}} for {{$payment_page_data['title']}}">
        <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://razorpay.com/favicon.png'}}">
        <meta property="og:image:width" content="276px">
        <meta property="og:image:height" content="276px">
        <meta property="og:description" content="{{$meta_description}}">

        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />


        <style>
            body {
                background-color: {{($payment_page_data['settings']['theme'] === 'dark') ? $dark_theme_color : $light_theme_color}};
            }
        </style>

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
                is_test_mode: true,
                merchant: data.merchant,
                payment_page_data: data.payment_link,
                context: {
                  page_title: data.payment_link.title,
                  form_title: 'Payment Details'
                },
              };

            function renderPaymentPage() {
                window.RZP.renderApp('paymentpage-container', templateData);
            }
        </script>

        <script src="https://cdn.razorpay.com/static/analytics/bundle.js" defer></script>
        <script src="{{env('AWS_CF_CDN_URL')}}/static/hosted/wysiwyg.js" onload="renderPaymentPage()" async defer></script>
        <script src="https://checkout.razorpay.com/v1/checkout.js" async defer></script>
    </head>

    <body>
        <div id="paymentpage-container">
        </div>
    </body>
</html>
