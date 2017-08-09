<!doctype html>
<html>
<head>
  <title></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
.loader{height:24px;width:24px;border-radius:50%;display:inline-block;opacity:0;
animation:lo .8s infinite linear;-webkit-animation:lo .8s infinite linear;
transition:0.3s;-webkit-transition:0.3s;
border:2px solid #29B7D6;border-top-color:transparent}
.vis{opacity:1}
@keyframes lo{to{transform:rotate(360deg)}}@-webkit-keyframes lo{to{-webkit-transform:rotate(360deg)}}
  </style>
</head>
<body>
  <img src="{{$data['cdn']}}/logo.svg" id="logo" height="70px" style="margin:30px auto 0">
  <div class="loader vis" style="position:absolute;top:115px;left:50%;margin-left:-12px"></div>
  <link href="{{$data['cdn']}}/static/payment_redirect/bundle.css" rel="stylesheet"></link>
  <script type="text/javascript">
    var data = {!!utf8_json_encode($data['data'])!!};
  </script>
  <script type="text/javascript" src="{{$data['cdn']}}/static/payment_redirect/bundle.js"></script>
  {{-- Do not remove below form — needed to run tests --}}
  <form class="card" id="otpform" name="otpform" action="{{$data['data']['request']['url']}}" method="post">
    <input id='otp' type="hidden" name="otp" maxlength="6">
  </form>
  <form id="form2" name="form2">
    <input type="hidden" name="type" value="{{$data['data']['type']}}">
    <input type="hidden" name="gateway" value="{{$data['data']['gateway']}}">
  </form>
</body>
</html>
