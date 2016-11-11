<!doctype html>
<html>
  <head>
    <title>Invoice ·</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  </head>
<body>
  <div id='success' style='display: none'></div>
</body>
  <script>
    var data = {!!utf8_json_encode($data)!!};
    data.handler = function(response) {
      document.querySelector('success').style.display = 'block';
    }
    Razorpay.open(data);
  </script>
