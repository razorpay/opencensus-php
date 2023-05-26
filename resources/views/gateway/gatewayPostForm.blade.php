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
{{--<body onload="document.form3.submit()">--}}
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
      @if (isset($data['amount']))
        <div id="amt">
          <div style="font-size:12px;color:#757575;line-height:15px;margin-bottom:5px;text-align:right">PAYING</div>
          <div style="font-size:20px;line-height:24px;">{!! e(encode_currency($data['amount']), false) !!}</div>
        </div>
      @endif
    </div>
    <div id="ldr"></div>
    <div id="txt">
      <div style="display:inline-block;vertical-align:middle;white-space:normal;">
        <h2 id='title'>Loading Bank page&#x2026;</h2>
        <p id='msg'>Please wait while we redirect you to your Bank page.</p>
      </div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
    @if (isset($data['show_independence_image']) && $data['show_independence_image'])
    <div id='ftr_new'>
      <div style="display:inline-block;">Secured by <img style="vertical-align:middle;margin-bottom:5px;" height="20px" src={{ $data['checkout_logo'] }}></div>
      <img src="https://cdn.razorpay.com/static/assets/15aug.png" style="vertical-align:middle;margin-bottom:5px;" height="34px" />
    </div>
    @else
    <div id='ftr'>
      <div style="display:inline-block;">Secured by <img style="vertical-align:middle;margin-bottom:5px;" height="20px" src={{ $data['checkout_logo'] }}></div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
    @endif
  </div>


{{-- Continue authetication--}}
  @if (isset($data['request']['auth_step']) && $data['request']['auth_step'] =="3ds2Auth")

    <form id="form3" name="form3"
        action= "{{$data['request']['notificationUrl']}}"
        method="post" onsubmit="return true;">
        <input type="hidden" id="java_enabled" name="browser[java_enabled]" value="false">
        <input type="hidden" id="javascript_enabled" name="browser[javascript_enabled]" value="false">
        <input type="hidden" id="timezone_offset" name="browser[timezone_offset]" value="0">
        <input type="hidden" id="color_depth" name="browser[color_depth]" value="0">
        <input type="hidden" id="screen_width" name="browser[screen_width]" value="0">
        <input type="hidden" id="screen_height" name="browser[screen_height]" value="0">
        <input type="hidden" id="language" name="browser[language]" value="en-US">
        <input type="hidden" id="auth_step" name="auth_step" value="{{$data['request']['auth_step']}}">
    </form>
{{--  Iframe--}}
    <form id="form4" name="3dsMethodPostingForm" action="{{$data['request']['url']}}" method="post" target="hidden-form">
      @foreach ($data['request']['content'] as $key => $value)
          <input type="hidden" name="{{$key}}" value="{{$value}}">
      @endforeach
     </form>
    <iframe style="display:none" name="hidden-form"></iframe>
  @else
{{--        3ds 1.0 or 3ds 2.0 OTP submission--}}
        <form id="form1" name="form1" action="{{$data['request']['url']}}" method="post" onsubmit="return true;">
        @foreach ($data['request']['content'] as $key => $value)
          <input type="hidden" name="{{$key}}" value="{{$value}}">
        @endforeach
        </form>
        <form id="form2" name="form2">
          <input type="hidden" name="type" value="{{$data['type']}}">
          <input type="hidden" name="gateway" value="{{$data['gateway']}}">
        </form>
  @endif


  @if (isset($data['request']['auth_step']) && $data['request']['auth_step'] =="3ds2Auth")
      <script>
      document.getElementById("form4").submit();
      setTimeout(function() {
          const javaEnabled = navigator.javaEnabled();
          const javascriptEnabled = true;
          const date1 = new Date();
          const timeZoneOffset = date1.getTimezoneOffset();
          const colorDepth = screen.colorDepth;
          const screenWidth = screen.width;
          const screenHeight = screen.height;
          const language = navigator.language;
          document.getElementById("java_enabled").value = javaEnabled;
          document.getElementById("javascript_enabled").value = javascriptEnabled;
          document.getElementById("timezone_offset").value = timeZoneOffset;
          document.getElementById("color_depth").value = colorDepth;
          document.getElementById("screen_width").value = screenWidth;
          document.getElementById("screen_height").value = screenHeight;
          document.getElementById("language").value = language;
          document.form3.submit();
      }, 2000);
      </script>
  @endif
  <script>
      setTimeout(function() {
      document.body.className = 'loaded';
    }, 10);

    @if (!isset($data['request']['auth_step']) || $data['request']['auth_step'] !="3ds2Auth")
        document.form1.submit();
    @endif

    setTimeout(function(){
      document.getElementById('title').innerHTML = 'Still trying to load...';
      document.getElementById('msg').innerHTML = 'The bank page is taking time to load.';
    }, 10000);
  </script>
</body>
</html>
