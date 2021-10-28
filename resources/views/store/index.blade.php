<!DOCTYPE html>
<html>
<head>
    <title>{{{ $data['store']['title'] }}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Pay at {{{ $data['store']['title'] }}} by {{{ $data['merchant']['name'] }}}">

    <meta property="og:title" content="{{{ $data['store']['title'] }}}">
    <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://cdn.razorpay.com/static/assets/logo/rzp.png'}}">
    <meta property="og:image:width" content="276px">
    <meta property="og:image:height" content="276px">
    <meta property="og:description" content="Pay at {{{ $data['store']['title'] }}} by {{{ $data['merchant']['name'] }}}">

    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="{{{ $data['store']['title'] }}}" />
    <meta name="twitter:description" content="Pay at {{{ $data['store']['title'] }}} by {{{ $data['merchant']['name'] }}}" />
    <meta name="twitter:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://cdn.razorpay.com/static/assets/logo/rzp.png'}}" />

    <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />
</head>
<style type="text/css">
    @font-face {
        font-family: 'Lato-Bold';
        font-weight: 300;
        src: url('https://mandatehq-static.mandatehq.com/fonts/Lato-Bold.ttf') format('truetype');
    }

    @font-face {
        font-family: 'Lato-Light';
        font-weight: 400;
        src: url('https://mandatehq-static.mandatehq.com/fonts/Lato-Light.ttf') format('truetype');
    }

    @font-face {
        font-family: 'Lato-Regular';
        font-weight: 700;
        src: url('https://mandatehq-static.mandatehq.com/fonts/Lato-Regular.ttf') format('truetype');
    }
</style>
<body>
<div id="root"></div>
<script type="text/javascript">
    window.store_data = {!! utf8_json_encode($data) !!};
    window.rzp_stored_slug = "{{{ $data['store']['slug'] }}}";
</script>
<script src="{{env('AWS_CF_CDN_URL')}}/hostedpages/stores/build/browser/entry.js"></script>
</body>
</html>
