<?php
$fonts = 'https://cdn.razorpay.com/lato2';
// $checkout = 'http://checkout.pronav.in/dist';
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
 </head>
  <body>
    <div id="loading"><div></div><div></div><div></div></div>
<?php
if (isset($error))
{
?>
    <script>
      var error = <?= json_encode($error);?>;

      function sendMessage(message){
        if(typeof window.CheckoutBridge == 'object'){
          CheckoutBridge['on' + message.event]();
        } else {
          message.source = 'frame';
          window.parent.postMessage(JSON.stringify(message), '*');
        }
      }

      if(typeof error === 'object'){
        alert(error.description);
        sendMessage({event: 'dismiss'});
        setTimeout(function(){
          sendMessage({event: 'hidden'});
        })
      }
    </script>
  </body>
<?php
}
else
{
?>
    <link rel="stylesheet" href="<?= $checkout ?>/v1/css/checkout.css">
  </body>
  <script>
    var payment_methods = <?= $methods ?>;
    var fee_bearer  = <?= $feeBearer ?>;
  </script>
  <script src="<?= $checkout ?>/v1/checkout-frame.js"></script>

<?php
}
?>
</html>
