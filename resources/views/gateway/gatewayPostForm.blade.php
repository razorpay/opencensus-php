<!doctype html>
<html style="height:100%;width:100%;">
<head>
<title>Processing, Please Wait...</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="{{$data['theme']['color']}}">
<script>
try{
  var payment_id = "{{$data['payment_id']}}";
  if (typeof(CheckoutBridge) !== 'undefined' && typeof(CheckoutBridge.setPaymentID) === 'function') {
    CheckoutBridge.setPaymentID(payment_id);
  } else if(window.opener){
  opener.setPaymentID(payment_id);
  }
} catch(e){}
</script>
@if (isset($data['production']) and $data['production'] === true)
<script>
  var events = {
    page: 'gateway_postform',
    props: {
    @if (isset($data['payment_id']))
      payment_id: '{{$data['payment_id']}}',
    @endif
    @if (isset($data['merchant_id']))
      merchant_id: '{{$data['merchant_id']}}',
    @endif
    },
    load: true,
    unload: true
  }
</script>
@include('partials.track')
@endif
@include('partials.redirectStyles')
</head>
<body onload="document.form1.submit()">
  <div id='bg'></div>
  <div style="display:inline-block;vertical-align:middle;height:100%"></div>
  <div id='cntnt'>
    <div id="hdr">
      @if (isset($data['image']))
      <div id="logo"><img src="{{$data['image']}}"/></div>
      @endif
      <div id='name'>
        @if (isset($data['name']))
          {{$data['name']}}
        @else
          Redirecting...
        @endif
      </div>
      @if (isset($data['amount']))
        <div id="amt">
          <div style="font-size:12px;color:#757575;line-height:15px;margin-bottom:5px;text-align:right">PAYING</div>
          <div style="font-size:20px;line-height:24px;">{{ encode_currency($data['amount']) }}</div>
        </div>
      @endif
    </div>
    <div id="ldr"></div>
    <div id="txt">
      <div style="display:inline-block;vertical-align:middle;white-space:normal;">
        <h2 id='title'>Loading Bank page&#x2026;</h2>
        <p id='msg'>Please wait while we redirect you to your Bank page</p>
      </div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
    <div id='ftr'>
      <div style="display:inline-block;">Secured by <img style="vertical-align:middle;margin-bottom:5px;" height="20px" src="https://cdn.razorpay.com/logo.svg"></div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
  </div>
  <form id="form1" name="form1" action="{{$data['request']['url']}}" method="post" onsubmit="return true;">
  @foreach ($data['request']['content'] as $key => $value)
    <input type="hidden" name="{{$key}}" value="{{$value}}">
  @endforeach
  </form>
  <form id="form2" name="form2">
    <input type="hidden" name="type" value="{{$data['type']}}">
    <input type="hidden" name="gateway" value="{{$data['gateway']}}">
  </form>
  <script>
    setTimeout(function() {
      document.body.className = 'loaded';
    }, 10);

    setTimeout(function(){
      document.getElementById('title').innerHTML = 'Still trying to load...';
      document.getElementById('msg').innerHTML = 'The bank page is taking time to load.';
    }, 10000);
  </script>
</body>
</html>
