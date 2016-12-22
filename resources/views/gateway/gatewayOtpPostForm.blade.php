<!doctype html>
<html>
<head>
  <title></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php
    $basepath = 'http://static.razorpay.dev/payment_redirect';
    $basepath = 'https://cdn.razorpay.com/static/payment_redirect';
  ?>
  <link href="{{$basepath}}/bundle.css" rel="stylesheet"></link>
</head>
<body>
  <script type="text/javascript">
    var data = {!!utf8_json_encode($data)!!};
  </script>
  <script type="text/javascript" src="{{$basepath}}/bundle.js"></script>
</body>
</html>
