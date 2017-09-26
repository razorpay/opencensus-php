<!doctype html>
<html style="height:100%;width:100%;">
<head>
<meta charset="utf-8">
<title>Processing, Please Wait...</title>
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

<style>
*{
  -webkit-box-sizing:border-box;
  -moz-box-sizing:border-box;
  box-sizing:border-box;
  margin:0;
  padding:0;
}

body{
  background:#f5f5f5;
  overflow:hidden;
  text-align:center;
  height:100%;
  white-space:nowrap;
  margin:0;
  padding:0;
  font-family:-apple-system, BlinkMacSystemFont,ubuntu,verdana,helvetica,sans-serif;
}

#bg {
  position:absolute;
  bottom:50%;
  width:100%;
  height:50%;
  background:{{$data['theme']['color']}};
  margin-bottom:90px;
}
#cntnt {
  position:relative;
  width:100%;
  vertical-align: middle;
  display: inline-block;
  margin: auto;
  max-width:420px;
  min-width:280px;
  height:95%;
  max-height:360px;
  background:#fff;
  z-index:9999;
  box-shadow:0 0 20px 0 rgba(0,0,0,0.16);
  border-radius:4px;
  overflow:hidden;
  padding:24px;
  box-sizing:border-box;
  text-align:left;
}
#ftr {
  position:absolute;
  left:0;
  right:0;
  bottom:0;
  height:80px;
  background:#f5f5f5;
  text-align:center;
  color:#212121;
  font-size:14px;
  letter-spacing:-0.3px;
}

#ldr {
  width:100%;
  height:3px;
  position:relative;
  margin-top:16px;
  border-radius:3px;
  overflow:hidden;
}

#ldr::before, #ldr::after {
  content:'';
  position:absolute;
  top:0;
  bottom:0;
  width:100%;
}

#ldr::before {
  top:1px;
  border-top:1px solid #bcbcbc;
}

#ldr::after {
  background:{{$data['theme']['color']}};
  width:0%;
  transition:20s cubic-bezier(0,0.1,0,1);
}

.loaded #ldr::after {
  width:90%;
}

#logo {
  width:48px;
  height:48px;
  padding:8px;
  border:1px solid #e5e5e5;
  border-radius:3px;
  text-align:center;
}

#hdr {
  min-height:48px;
  position:relative;
}

#logo, #name, #amt {
  display:inline-block;
  vertical-align:middle;
  letter-spacing:-0.5px;
}

#amt {
  position:absolute;
  right:0;
  top:0;
  background:#fff;
  color:#212121;
}

#name {
  line-height:48px;
  margin-left:12px;
  font-size:16px;
  max-width:140px;
  overflow:hidden;
  text-overflow:ellipsis;
  color:#212121;
}

#logo+#name{
  line-height:20px;
}

#txt {
  height:200px;
  text-align:center;
}

#title {
  font-size:20px;
  line-height:24px;
  margin-bottom:8px;
  letter-spacing:-0.3px;
}

#msg, #cncl {
  font-size:14px;
  line-height:20px;
  color:#757575;
  margin-bottom:8px;
  letter-spacing:-0.3px;
}

#cncl {
  text-decoration:underline;
  cursor:pointer;
}

#logo img {
  max-width:100%;
  max-height:100%;
  vertical-align:middle;
}

@media (max-height:580px), (max-width:420px) {
  #bg{
     display:none;
  }
  body {
    background:{{$data['theme']['color']}};
  }
}

@media (max-width:420px){
  #cntnt {
    padding:16px;
    width:95%;
  }
  #name {
    margin-left:8px;
  }
}
</style>
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
