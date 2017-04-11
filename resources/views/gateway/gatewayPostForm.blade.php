<!doctype html>
<html style="height:100%;width:100%;">
<head>
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
          <div style="font-size:20px;line-height:24px;">₹{{$data['amount']}}</div>
        </div>
      @endif
    </div>
    <div id="ldr"></div>
    <div id="txt">
      <div style="display:inline-block;vertical-align:middle;white-space:normal;">
        <h2 id='title'>Loading Bank page…</h2>
        <p id='msg'>Please wait while we redirect you to your Bank page</p>
      </div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
    <div id='ftr'>
      <div style="display:inline-block;">Secured by <img style="vertical-align:middle;margin-bottom:5px;" height="20px" src="data:image/png;base64,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"></div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
  </div>
  <div style="display:inline-block;vertical-align:middle;height:100%"></div>
  <form id="form1" name="form1" action="{{$data['request']['url']}}" method="post" onsubmit="return true;">
  @foreach ($data['request']['content'] as $key => $value)
     <input type="hidden" name="{{$key}}" value="{{$value}}">
     <br />
  @endforeach
  </form>
  <br>
  <form id="form2" name="form2">
     <input type="hidden" name="type" value="{{$data['type']}}">
     <input type="hidden" name="gateway" value="{{$data['gateway']}}">
  </form>
  <script>
    var gel = document.getElementById.bind(document);
    setTimeout(function() {
      document.body.className = 'loaded';
    }, 10);

    setTimeout(function(){
      gel('title').innerHTML = 'Still trying to load...';
      gel('msg').innerHTML = 'The bank page is taking time to load.';
    }, 10000);
  </script>
</body>
</html>
