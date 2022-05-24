<?php
$payment_page_data          = $data['payment_link'];
$is_test_mode               = $data['is_test_mode'] ?? false;
$has_udf                    = (empty($udf_schema) === false);
$meta_title                 = 'Payment request from ' . $data['merchant']['name'];
$meta_description           = 'Use this link to enter the amount and pay securely via Razorpay: ' . $data['payment_link']['handle_url'];
$dark_theme_color           = '#383838';
$light_theme_color          = '#efefef';
$is_error_view              = isset($request_params['error']['description']);
?>


<!doctype html>
<html lang="en">
<head>
    <title>Razorpay.me - {{{ $data['merchant']['name'] }}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    @include('hostedpage.partials.robot')
    <meta name="description" content="{{{ $meta_description }}}">

    <meta property="og:title" content="{{{ $meta_title }}}">
    <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://cdn.razorpay.com/static/assets/logo/rzp.png'}}">
    <meta property="og:image:width" content="276px">
    <meta property="og:image:height" content="276px">
    <meta property="og:description" content="{{{ $meta_description }}}">

    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="{{{ $meta_title }}}" />
    <meta name="twitter:description" content="{{{ $meta_description }}}" />
    <meta name="twitter:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://cdn.razorpay.com/static/assets/logo/rzp.png'}}" />

    <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

    <link
      href="https://fonts.googleapis.com/css2?family=Lato:wght@400;600;700&display=swap"
      rel="stylesheet"
    />

    <link
      rel="stylesheet"
      type="text/css"
      href="{{env('AWS_CF_CDN_URL')}}/static/payment-handle/bundle.css"
    />

    @if (isset($data['environment']))
        @if ($data['environment'] !== 'production')
            <script>
                var Razorpay = {
                    config: {
                        api: "https://api.razorpay.com/"
                    }
                }
            </script>
        @endif
    @endif

    <script>
        var data = {!!utf8_json_encode($data)!!};

        var templateData = {
          key_id: data.key_id,
          base_url: data.base_url,
          merchant: data.merchant,
          org: data.org,
          view_preferences: data.view_preferences,
          paymentData: data.payment_link,
          environment: data.environment,
          payment_handle_amount: data.payment_handle_amount,
          is_preview: data.is_test_mode,
          keyless_header: data.keyless_header,
        };
    </script>

    @if ($is_error_view === false)
        <script>
            function renderApp() {
                window.RZP.renderApp('root', templateData);
            }
        </script>

        <script src="https://cdn.razorpay.com/static/analytics/bundle.js" defer></script>
        <script src="https://cdn.razorpay.com/static/assets/color.js" defer></script>
        <script src="{{env('AWS_CF_CDN_URL')}}/static/payment-handle/bundle.js" defer onload="renderApp()"></script>
        <script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
    @else
        @include('payment_link.partials.post_screen')
    @endif
</head>

<body>
    <div id="root">
        @if ($is_error_view === true)
            @include('hostedpage.partials.success', ['error' => true])
            <div id="post-msg">
                <div>{{$request_params['error']['description'] ?? 'If any amount is deducted, it will be automatically refunded'}}</div>
                <a href="{{{$payment_page_data['short_url']}}}">Retry Payment</a>
            </div>
        @endif
    </div>
</body>
</html>
