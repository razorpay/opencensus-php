<?php
    $payment_page_data          = $data['payment_link'];
    $is_test_mode               = $data['is_test_mode'] ?? false;
    $has_udf                    = (empty($udf_schema) === false);
    $description_meta_text      = ($payment_page_data['description'] and json_decode($payment_page_data['description'], true)['metaText']) ? json_decode($payment_page_data['description'], true)['metaText'] : null;
    $meta_description           = $description_meta_text ? $description_meta_text : 'Payment request by '. $data['merchant']['name'];
    $dark_theme_color           = '#383838';
    $light_theme_color          = '#efefef';
    $is_error_view              = isset($request_params['error']['description']);
    $is_preview                 = request()->get('preview') === 'true';
    $optimised_web_vitals       = $data['merchant']['optimised_web_vitals'] === 'on';
    $crossorigin_enabled        = array_get($data, 'view_preferences.crossorigin_enabled', 'off') === 'on';
?>


<!doctype html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
        @include('hostedpage.partials.robot')
        <meta name="description" content="{{{ $meta_description }}}">

        <meta property="og:title" content="Pay for {{{ $payment_page_data['title'] }}} by {{{ $data['merchant']['name'] }}}">
        <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://cdn.razorpay.com/static/assets/logo/rzp.png'}}">
        <meta property="og:image:width" content="276px">
        <meta property="og:image:height" content="276px">
        <meta property="og:description" content="{{{ $meta_description }}}">

        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="Pay for {{{ $payment_page_data['title'] }}} by {{{ $data['merchant']['name'] }}}" />
        <meta name="twitter:description" content="{{{ $meta_description }}}" />
        <meta name="twitter:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://cdn.razorpay.com/static/assets/logo/rzp.png'}}" />

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

            var paymentPageData = data.payment_link;
            paymentPageData.description = paymentPageData.description ? JSON.parse(paymentPageData.description).value : null;

            var requestParams = {!!utf8_json_encode($request_params)!!};

            var templateData = {
                key_id: data.key_id,
                base_url: data.base_url,
                is_test_mode: data.is_test_mode,
                merchant: data.merchant,
                org: data.org,
                payment_page_data: data.payment_link,
                context: {
                  page_title: data.payment_link.title,
                  form_title: 'Payment Details'
                },
                requestParams: requestParams,
                view_preferences: data.view_preferences,
                keyless_header: data.keyless_header,
                checkout_2_enabled: data.checkout_2_enabled ?? false,
                is_pp_batch_upload: data.is_pp_batch_upload ?? false,
              };
        </script>

        @if ($is_error_view === false)
            <script>
                function renderPaymentPage() {
                    window.RZP.renderApp('paymentpage-container', templateData);
                }
            </script>
            <!-- Temporary polyfill for analytics -->
            <script src="https://polyfill.io/v3/polyfill.min.js?features=URL%2CURLSearchParams"></script>
            @if($optimised_web_vitals === false)
                <script src="{{env('AWS_CF_CDN_URL')}}/static/analytics/bundle.js" defer></script>
                <script src="https://cdn.razorpay.com/static/assets/color.js" defer></script>
                <script 
                    src="{{env('AWS_CF_CDN_URL')}}/static/hosted/wysiwyg.js" 
                    onload="renderPaymentPage()" 
                    defer
                    {{ $crossorigin_enabled === true ? "crossorigin" : "" }}
                ></script>
            @else
                @if($is_preview === false)
                    <script src="{{env('AWS_CF_CDN_URL')}}/static/analytics/bundle.js" defer></script>
                @endif
                <script src="https://cdn.razorpay.com/static/assets/color.js" defer></script>

                <link rel="preconnect" href="https://fonts.googleapis.com"/>
                <link href="https://fonts.googleapis.com/css?family=Muli:400,700&display=swap" rel="stylesheet">
                <script src="https://cdn.razorpay.com/static/assets/quilljs/1.3.6/quill.min.js" defer ></script>

                <script 
                    src="{{env('AWS_CF_CDN_URL')}}/static/hosted/wysiwyg.js" 
                    onload="renderPaymentPage()" 
                    defer
                    {{ $crossorigin_enabled === true ? "crossorigin" : "" }}
                ></script>
                <link rel="stylesheet" href="https://cdn.razorpay.com/static/assets/social-share/icons.css" />
            @endif
        @else
            @include('payment_link.partials.post_screen')
        @endif
    </head>

    <body>
        <div id="paymentpage-container">
            @if ($is_error_view === true)
                @include('hostedpage.partials.success', ['error' => true])
                <div id="post-msg">
                    <div>{{$request_params['error']['description'] ?? 'If any amount is deducted, it will be automatically refunded'}}</div>
                    <a href="{{{$payment_page_data['short_url']}}}">Retry Payment</a>
                </div>
            @endif
        </div>
        <!-- Adding checkout scripts after initial load -->
        @if ($is_error_view === false and ($optimised_web_vitals === false or $is_preview === false))
            <script>
                window.addEventListener('load', function() {
                    setTimeout(function () {
                        var script = document.createElement("script");
                        script.src = "https://checkout.razorpay.com/v1/checkout.js";
                        document.body.appendChild(script);
                    }, 2000);
                });
            </script>
        @endif
    </body>
</html>
