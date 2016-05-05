<!doctype html><html style="height:100%"><head><title>Razorpay - Payment in progress</title>
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
#closer{position:fixed;bottom:20px;width:100%;left:0;color:#7f7f7f;font-size:14px;}
</style>
<meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
</head><body>
<div id="text"><div id="icon"></div><br>Payment<br>
</div>
<div id="closer">
You can safely close this tab
</div>
<script>

// Do not remove the below 'callback data' comments because they help
// during tests for extracting callback data from js

// Callback data //
var data = {{utf8_json_encode($data)}};
// Callback data //

var s = 'razorpay_payment_id' in data;
data = JSON.stringify(data);
if(window.CheckoutBridge){
  if(typeof CheckoutBridge.oncomplete=='function'){CheckoutBridge.oncomplete(data)}
} else {
  try {
    localStorage.setItem('on_payment_complete', data);
  } catch (e) {
    document.cookie = "onComplete="+data+";expires=Fri, 31 Dec 9999 23:59:59 GMT;path=/";
  }
}

function g(id){return document.getElementById(id)}
function razorpay_callback(){return data}

var t = g('text')
t.innerHTML += s ? 'Successful' : 'Failed'
t.className = 'show ' + (s ? 's' : 'f')
g('icon').innerHTML = s ? '&#10004' : '!'

onerror = function(message){
  message = JSON.stringify({
    access_token: '3cddb790e27342ad86f431498a1f8342',
    data: {
      environment: 'production',
      platform: 'popup',
      body: {
        message: {
          body: message
        }
      }
    }
  })
  var xhr = new XMLHttpRequest()
  xhr.open('post', 'https://api.rollbar.com/api/1/item/', true)
  xhr.setRequestHeader('Content-Type', 'application/json')
  xhr.send(message)
}
if(!window.CheckoutBridge){
  if(window.opener) {
    try{opener.onComplete(data)&&close()}catch(e){onerror(e.message)}
    opener.postMessage(data,'*')
  }
  if(/\(iP.+(Cr|Fx)iOS/.test(navigator.userAgent))
    setTimeout(close, 1000);
}

</script></body></html>
