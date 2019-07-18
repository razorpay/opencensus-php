<!doctype html><html style="height:100%"><head><title>Razorpay - Payment in progress</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8"><style>
body{background:#fff;font-family:ubuntu,helvetica,verdana,sans-serif;margin:0;padding:0;width:100%;height:100%;text-align:center;display:table}
#text{vertical-align: middle; display: none; text-transform: uppercase; font-weight: bold; font-size: 30px; line-height: 40px}
#icon{font-size: 60px;color: #fff; border-radius: 50%; width: 80px; height: 80px; line-height: 80px; margin: -60px auto 20px; display: inline-block}
#text.show{display:table-cell}
#text.s{color:#61BC6D;}
#text.s #icon{background:#61BC6D}
#text.f{color:#EF6050;}
#text.f #icon{background:#EF6050}
#delayed-prompt {position: fixed; top:70%; left: 0; right: 0;}
.text {transition: 0.2s opacity; position: absolute; top: 0; width: 100%; opacity: 0; transition-delay: 0.2s;}
.show-early .early, .show-late .late {opacity: 1}
.show-early .late, .show-late .early {opacity: 0}
#proceed-btn {color: #528ff0; text-decoration: underline; cursor: pointer; -webkit-tap-highlight-color: transparent;}
</style>
<meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
</head><body>
<div id="text"><div id="icon"></div><br>Payment<br>
</div>
<div id="delayed-prompt">
  <div class="early text">Redirecting...</div>
  <div class="late text" id="proceed-btn">Click here to proceed</div>
</div>
<script>

{{-- Do not remove the below 'callback data' comments because they help
 during tests for extracting callback data from js --}}

// Callback data //
var data = {!!utf8_json_encode($data)!!};
// Callback data //

var s = 'razorpay_payment_id' in data;
data = JSON.stringify(data);
if (window.CheckoutBridge) {
  if (typeof CheckoutBridge.oncomplete == 'function') {
    function onComplete() { CheckoutBridge.oncomplete(data); }
    setTimeout(onComplete, 30);
    setTimeout(function () {
      g('delayed-prompt').classList.add('show-early');
    }, 500);
    setTimeout(function () {
      g('delayed-prompt').classList.add('show-late');
      g('delayed-prompt').classList.remove('show-early');
    }, 2000);
    g('proceed-btn').onclick = onComplete;
  }
} else {
  document.cookie =
    'onComplete=' + data + ';expires=Fri, 31 Dec 9999 23:59:59 GMT;path=/';
  try {
    localStorage.setItem('onComplete', data);
  } catch (e) {}
}

var iosCheckoutBridgeNew = ((window.webkit || {}).messageHandlers || {})
  .CheckoutBridge;

if (iosCheckoutBridgeNew) {
  iosCheckoutBridgeNew.postMessage({
    action: 'success',
    body: JSON.parse(data)
  });
}

function g(id) {
  return document.getElementById(id);
}
function razorpay_callback() {
  return data;
}

var t = g('text');
t.innerHTML += s ? 'Successful' : 'Failed';
t.className = 'show ' + (s ? 's' : 'f');
g('icon').innerHTML = s ? '&#10004' : '!';

if (!window.CheckoutBridge) {
  try { window.opener.onComplete(data) } catch(e){}
  try { (window.opener || window.parent).postMessage(data, '*') } catch(e){}
  setTimeout(close, 999);
}

</script></body></html>
