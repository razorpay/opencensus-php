<!doctype html>
<html>
<head>
  <title></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php
    $basepath = 'http://static.razorpay.dev/payment_redirect';
    $basepath = 'https://cdn.razorpay.com/static/payment_redirect';
  ?>
  <style>
.loader{height:24px;width:24px;border-radius:50%;display:inline-block;opacity:0;
animation:lo .8s infinite linear;-webkit-animation:lo .8s infinite linear;
transition:0.3s;-webkit-transition:0.3s;
border:2px solid #29B7D6;border-top-color:transparent}
.vis{opacity:1}
@keyframes lo{to{transform:rotate(360deg)}}@-webkit-keyframes lo{to{-webkit-transform:rotate(360deg)}}
  </style>
  <link href="{{$basepath}}/bundle.css" rel="stylesheet"></link>
</head>
<body>
  <img src="https://cdn.razorpay.com/logo.svg" id="logo" height="70px" style="display:block;margin:30px auto 0;">
  <div class="loader vis" style="position:absolute;top:115px;left:50%;margin-left:-12px"></div>
  <script type="text/javascript">
    var data = {!!utf8_json_encode($data)!!};
  </script>
  <script type="text/javascript" src="{{$basepath}}/bundle.js"></script>
</body>
</html>
