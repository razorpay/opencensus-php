<!doctype html>
<html>
<head>
  <title></title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
@if ($data['production'])
<script>
  var events = {
    page: 'gateway_otp_postform',
    props: {
      payment_id: '{{$data['data']['payment_id']}}',
    },
    load: 1,
    unload: 1
  }
</script>
@endif
<body>
    <div id="preloading">
        <style>
            body {
                background: #f4f4f4;
            }
        </style>
        @include('partials.loader')
        <img src="{{$data['cdn']}}/logo.svg" id="logo" height="35px" style="margin:30px auto 10px; display:block">
    </div>
  <script type="text/javascript">
    // input data //
    var data = {!!utf8_json_encode($data['data'])!!};
    // input data //
    try { CheckoutBridge.setPaymentID(data.payment_id) } catch(e){}
  </script>
  <div id="app"></div>
  <script type="text/javascript" src="{{$data['cdn']}}/static/payment_redirect/bundle.js" charset="utf-8"></script>
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
