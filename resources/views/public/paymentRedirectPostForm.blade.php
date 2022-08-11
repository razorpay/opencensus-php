<!doctype html>
<html style="height:100%;width:100%;">
<head>
<title>Processing, Please Wait...</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="{{$data['theme']['color']}}">
<meta http-equiv="refresh" content="0;url={{ $data['request']['url'] }}" />
@include('partials.redirectStyles')
</head>
@if (isset($data['production']) and $data['production'] === true)
<script>
  var events = {
    page: 'payment_redirect_postform',
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
<body>
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
    </div>
    <div id="ldr"></div>
    <div id="txt">
      <div style="display:inline-block;vertical-align:middle;white-space:normal;">
        <h2 id='title'>Loading Bank page&#x2026;</h2>
        <p id='msg'>Please wait while we redirect you to your Bank page</p>
      </div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
    @if (isset($data['show_independence_image']) && $data['show_independence_image'])
    <div id='ftr_new'>
      <div style="display:inline-block;">Secured by <img style="vertical-align:middle;margin-bottom:5px;" height="20px" src="https://cdn.razorpay.com/logo.svg"></div>
      <img src="https://cdn.razorpay.com/static/assets/15aug.png" style="vertical-align:middle;margin-bottom:5px;" height="34px" />
    </div>
    @else
    <div id='ftr'>
      <div style="display:inline-block;">Secured by <img style="vertical-align:middle;margin-bottom:5px;" height="20px" src="https://cdn.razorpay.com/logo.svg"></div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
    @endif
  </div>
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
