<!doctype html><html style="height:100%"><head><title>Razorpay - Payment in progress</title><style>
body{background:#fff;font-family:ubuntu,helvetica,verdana,sans-serif;margin:0;padding:0;width:100%;height:100%;text-align:center;display:table}
#text{vertical-align: middle; display: none; text-transform: uppercase; font-weight: bold; font-size: 30px; line-height: 40px}
#icon{font-size: 60px;color: #fff; border-radius: 50%; width: 80px; height: 80px; line-height: 80px; margin: -60px auto 20px; display: inline-block}
#text.show{display:table-cell}
#text.s{color:#61BC6D;}
#text.s #icon{background:#61BC6D}
#text.f{color:#EF6050;}
#text.f #icon{background:#EF6050}
</style></head><body>
<div id="text"><div id="icon"></div><br>Payment<br></div>
<script>
function c(name, value, days){
  if(days){var date = new Date();date.setTime(date.getTime()+(days*24*60*60*1000));var expires = "; expires="+date.toGMTString()}
  else var expires = "";
  document.cookie = name+"="+value+expires+"; path=/";
}
// Do not remove the below 'callback data' comments because they help
// during tests for extracting callback data from js

// Callback data //
var data = {{json_encode($data);}};
// Callback data //

if(window.CheckoutBridge){
  if(typeof CheckoutBridge.oncomplete=='function'){CheckoutBridge.oncomplete(JSON.stringify(data))}
} else {
  c('rzp', JSON.stringify(data));
  if(window.opener && typeof window.opener.postMessage == 'function') window.opener.postMessage(data, '*')
}

function razorpay_callback(){return JSON.stringify(data)}

var t = document.getElementById('text');
var s = 'razorpay_payment_id' in data;
t.innerHTML += s ? 'Successful' : 'Failed';
t.className = 'show ' + (s ? 's' : 'f');
document.getElementById('icon').innerHTML = s ? '&#10004' : '!';

</script></body></html>