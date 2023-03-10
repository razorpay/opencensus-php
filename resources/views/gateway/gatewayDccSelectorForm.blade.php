<!doctype html>
<html>
<head>
  <title></title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css?family=Muli:400,600,800" rel="stylesheet"/>
</head>
@if (isset($data['production']) and $data['production'] === true)
<script>
  var events = {
    page: 'gateway_dcc_postform',
    props: {
    @if (isset($data['data']['payment_id']))
      payment_id: '{{$data['data']['payment_id']}}',
    @endif
    @if (isset($data['data']['merchant_id']))
      merchant_id: '{{$data['data']['merchant_id']}}',
    @endif
    },
    load: true,
    unload: true
  }
</script>
@include('partials.track')
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
  <script type="text/javascript" src="{{$data['cdn']}}/static/otp/bundle.js" charset="utf-8"></script>
  {{-- Do not remove below form — needed to run tests --}}
  <form class="card" id="dccform" name="dccform" action="{{$data['data']['request']['url']}}" method="post">
      <input id='currency_request_id' type="hidden" name="currency_request_id" value="{{$data['data']['dcc_info']['currency_request_id']}}">
      <input id='dcc_currency' type="hidden" name="dcc_currency" value="{{$data['data']['dcc_info'][$data['data']['payment_method'].'_currency']}}">
      <input id='amount' type="hidden" name="amount" value="{{$data['data']['dcc_info']['all_currencies'][$data['data']['dcc_info'][$data['data']['payment_method'].'_currency']]['amount']}}">
      <input id='forex_rate' type="hidden" name="forex_rate" value="{{$data['data']['dcc_info']['all_currencies'][$data['data']['dcc_info'][$data['data']['payment_method'].'_currency']]['forex_rate']}}">
      <input id='fee' type="hidden" name="fee" value="{{$data['data']['dcc_info']['all_currencies'][$data['data']['dcc_info'][$data['data']['payment_method'].'_currency']]['fee']}}">
      <input id='conversion_percentage' type="hidden" name="conversion_percentage" value="{{$data['data']['dcc_info']['all_currencies'][$data['data']['dcc_info'][$data['data']['payment_method'].'_currency']]['conversion_percentage']}}">
  </form>
  <form id="form2" name="form2">
    <input type="hidden" name="type" value="{{$data['data']['type']}}">
    <input type="hidden" name="gateway" value="{{$data['data']['gateway']}}">
  </form>
</body>
</html>
