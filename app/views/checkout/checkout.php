<?php
$fonts = 'https://cdn.razorpay.com/lato';
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
    <style>@font-face{font-family:'lato';src:url("<?= $fonts ?>.eot?#iefix") format('embedded-opentype'),url("<?= $fonts ?>.woff2") format('woff2'),url("<?= $fonts ?>.woff") format('woff'),url("<?= $fonts ?>.ttf") format('truetype'),url("<?= $fonts ?>.svg#lato") format('svg');font-weight:normal;font-style:normal}</style>
    <link rel="stylesheet" href="<?= $checkout ?>/v1/css/checkout.css">
 </head>
  <body>
    <div id="loading"><div></div><div></div><div></div></div>
  </body>
  <script>
    var payment_methods = <?= $methods ?>;
  </script>
  <script src="<?= $checkout ?>/v1/checkout-frame.js"></script>
</html>