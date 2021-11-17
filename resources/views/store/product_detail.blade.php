<!DOCTYPE html>
<html>
<head>
    <title>{{{ $data['meta_tags']['meta_title'] }}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="{{{ $data['meta_tags']['meta_description'] }}}">

    <meta property="og:title" content="{{{ $data['meta_tags']['meta_title'] }}}">
    <meta property="og:image" content="{{{ $data['meta_tags']['meta_image'] }}}">
    <meta property="og:image:width" content="276px">
    <meta property="og:image:height" content="276px">
    <meta property="og:description" content="{{{ $data['meta_tags']['meta_description'] }}}">

    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="{{{ $data['meta_tags']['meta_title'] }}}" />
    <meta name="twitter:description" content="{{{ $data['meta_tags']['meta_description'] }}}" />
    <meta name="twitter:image" content="{{{ $data['meta_tags']['meta_image'] }}}" />

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
