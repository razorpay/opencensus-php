<?php
$host = $_SERVER['HTTP_HOST'];
$scheme = $_SERVER['HTTP_SCHEME'];

$checkoutVersions = [
    'live'  =>  'https://checkout.razorpay.com/v1/',
    'beta'  =>  'https://betacheckout.razorpay.com/v1/',
    'local' =>  getenv('CHECKOUT_URL')
];

$fontsVersions = [
    'live'  =>  'https://cdn.razorpay.com/lato2',
    'beta'  =>  'https://betacdn.razorpay.com/fonts/lato2',
    'local' =>  getenv('FONTS_URL')
];

$checkout = $checkoutVersions[$_GET['checkout_version']];
$fonts = $fontsVersions[$_GET['fonts_version']];

if ($host === 'beta.razorpay.com')
{
    $fonts = $fontsVersions['beta'];
    $checkout = $checkoutVersions['beta'];
}

if (! $checkout)
{
   $checkout = $checkoutVersions['live'];
}

if (! $fonts)
{
   $fonts = $fontsVersions['live'];
}

header('Cache-Control: no-transform, no-store, no-cache, must-revalidate');
?>
<!DOCTYPE html>
<html dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Razorpay Checkout</title>
    <link rel="icon" href="data:;base64,=">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <link rel="stylesheet" href="<?= $checkout ?>/v1/css/checkout.css">
  </head>
  <body></body>
  <script src="<?= $checkout ?>/v1/checkout-frame.js"></script>
  <style>@font-face{font-family:'lato';src:url("<?= $fonts ?>.eot?#iefix") format('embedded-opentype'),url("<?= $fonts ?>.woff2") format('woff2'),url("<?= $fonts ?>.woff") format('woff'),url("<?= $fonts ?>.ttf") format('truetype'),url("<?= $fonts ?>.svg#lato") format('svg');font-weight:normal;font-style:normal}</style>
</html>
