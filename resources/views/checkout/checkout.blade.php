<!DOCTYPE html>
<html dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Razorpay Checkout</title>
    <link rel="icon" href="data:;base64,=">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <style>@font-face{font-family:'lato';src:url("<?= $font ?>.eot?#iefix") format('embedded-opentype'),url("<?= $font ?>.woff2") format('woff2'),url("<?= $font ?>.woff") format('woff'),url("<?= $font ?>.ttf") format('truetype'),url("<?= $font ?>.svg#lato") format('svg');font-weight:normal;font-style:normal}</style>
    <link rel="stylesheet" href="<?= $css ?>">
  </head>
  <body></body>
  <script>
    function appendScript(){
      var script = document.createElement('script');
      script.src = "<?= $framejs ?>";
      document.body.appendChild(script);
    }
  </script>
  <script>
    @if (isset($error))
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
    @else
      var preferences = <?= json_encode($preferences) ?>;
      function appendScript(){
        var script = document.createElement('script');
        script.src = "<?= $framejs ?>";
        document.body.appendChild(script);
      }
    @endif
  </script>
  <script src="<?= $framejs ?>" crossorigin onerror="appendScript()"></script>
</html>
