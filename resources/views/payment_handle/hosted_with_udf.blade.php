<?php
$payment_page_data          = $data['payment_link'];
$is_test_mode               = $data['is_test_mode'] ?? false;
$has_udf                    = (empty($udf_schema) === false);
$description_meta_text      = ($payment_page_data['description'] and json_decode($payment_page_data['description'], true)['metaText']) ? json_decode($payment_page_data['description'], true)['metaText'] : null;
$meta_description           = $description_meta_text ? $description_meta_text : 'Payment request by '. $data['merchant']['name'];
$dark_theme_color           = '#383838';
$light_theme_color          = '#efefef';
$is_error_view              = isset($request_params['error']['description']);
$is_performance_optimized   = $data['view_preferences']['page_load_optimization_enabled'] === 'on' ? true : false;
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
                        api: "{{ config('app.url') }}/"
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
        };
    </script>

    @if ($is_error_view === false)
        <script>
            function renderApp() {
                window.RZP.renderApp('root', templateData);
            }
        </script>

        <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script>
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
