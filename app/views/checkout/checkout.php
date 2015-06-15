<!DOCTYPE html>
<html dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Razorpay Checkout</title>
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <link rel="stylesheet" href="<?= $checkout ?>/v1/css/checkout.css">
 </head>
  <body>
    <div id="loading"><div></div><div></div><div></div></div>
  </body>
  <script>
    var payment_methods = <?= $methods ?>;
  </script>
  <script src="<?= $checkout ?>/v1/checkout-frame.js"></script>
  <link href='//fonts.googleapis.com/css?family=Lato' rel='stylesheet' type='text/css'>
</html>
