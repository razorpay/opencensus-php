<?php
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
?>
<!doctype html><head><title>Razorpay - Payment in progress</title><meta charset="UTF-8"><meta name="Viewport" content="width=device-width, initial-scale=1" /></head>
<body>
<form id="postform" style="display: none" method="post"></form>
<script>
var g = function(id){return document.getElementById(id)}
function autosubmit(data){
  g('PaReq').value = data.PAReq;
  g('MD').value = data.paymentid;
  g('TermUrl').value = data.callbackUrl;
  var dcform = g('dcform');
  dcform.action = data.url;
  dcform.submit();
}

window.onmessage = function(message){
  handleMessage(message.data);
}

function c(name, value, days){
  if (days) {
    var date = new Date();
    date.setTime(date.getTime()+(days*24*60*60*1000));
    var expires = "; expires="+date.toGMTString();
  }
  else var expires = "";
  document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name){
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++){
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
  }
  return null;
}

// remove cookie
// TODO cookie with unique keys, so that one tab doesn't interfere another

setInterval(function(){
  receive_cookie = readCookie('rzp-receive')
  if(receive_cookie){
    handleMessage(JSON.parse(receive_cookie));
    c('rzp-receive', '', -1);
  }
}, 400)

function setOrderData(data){
  data.date = new Date().toDateString();
  for(var i in data) g(i).innerHTML = data[i];
  g('order').style.display = 'block';
}

function handleMessage(data){
  if(typeof data == 'string') data = JSON.parse(data);
  if(data.url){
    if(data.method == 'get'){
      location.href = data.url;
    } else if (data.method == 'post' && typeof data.content == 'object'){
      var postForm = document.getElementById('postform');
      var html = '';
      for(var i in data.content){
        html += '<input type="hidden" name="' + i + '" value="' + data.content[i] + '">'
      }
      postForm.innerHTML = html;
      postForm.action = data.url;
      postForm.submit();
    } else {
      var errorData = {
        error: {
          description: 'Server Error'
        }
      };
      var errorString = JSON.stringify(errorData);
      c('rzp', errorString);
      if(window.opener && typeof window.opener.postMessage == 'function'){
        window.opener.postMessage(errorString, '*');
      }
    }
  } else {
    if(typeof data.location !== 'undefined'){
      location.href = data.location;
    }
    else if(typeof data.autosubmit !== 'undefined'){
      autosubmit(data.autosubmit);
    }
  }
  if(data.metadata) setOrderData(data.metadata);
}

</script>
<style>
  html, body{height: 100%; margin: 0; padding: 0}
  body{user-select: none; text-align: center; color: #444; font-family: verdana, helvetica, sans-serif; font-size: 16px; line-height: 30px; white-space: nowrap;}
  .middlechild,.container{display: inline-block; vertical-align: middle; white-space: normal;}
  .middlechild{width: 1px; height: 90%;}
  .container{width: 80%; max-width: 900px; margin: 80px auto 0;}
  #logo{padding-bottom: 15px; margin: 0 auto;}
  #top{position: absolute; top: 20px; text-align: center; border-bottom: 2px solid #ddd; width: 80%; left: 10%;}
  .heading{text-transform: uppercase; font-size: 32px; letter-spacing: 1px; line-height: 40px;}
  #pro{border-radius: 6px; margin-top: 15px;}
  .powered-by{font-size: 50px;text-decoration: none; color: #999; margin-top: 10px; display: block; line-height: 60px}
  img{max-width: 100%; display: block; margin: 10px auto;}
  #description{clear:both; padding: 40px 0 10px; font-size: 22px; font-weight: bold;}
  #order{display: none; max-width: 600px; margin: 0 auto;}
  .amount{font-size: 50px; line-height: 60px; color: #29b3d2;}
@media (max-device-height: 450px),(max-device-width: 450px){
  .heading{font-size: 28px; line-height: 36px;}
}
</style>
<div id="top"><img src="/logo.gif" width="200" height="52" id="logo"></div>
<div class="container">
  <div class="heading">Processing Payment</div>
  <img src="/processing.gif" width="600" height="12" id="pro">
  <div id="order">
    <div id="name" style="float:left;"></div>
    <div id="date" style="float:right;"></div>
    <div id="description"></div>
    <div class="heading amount">&#xe600;<span id="amount"></span></div>
  </div>
  <div style="margin-top: 25px">Redirecting to bank page...</div>
  <img width="300px" src="data:image/jpg;base64,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"/>
<div class="autosubmit">
  <form method="POST" action="{{=it.data.url}}" id="rzp-dcform">
    <input type="hidden" id="PaReq" name="PaReq" value="{{=it.data.PAReq}}">
    <input type="hidden" id="MD" name="MD" value="{{=it.data.paymentid}}">
    <input type="hidden" id="TermUrl" name="TermUrl" value="{{=it.callbackUrl}}">
  </form>
</div>
</div>
<div class="middlechild"></div>
<script>
if (!window.CheckoutBridge){
  var msgObj = {
    source: 'popup',
    loaded: true
  }
  var msg = JSON.stringify(msgObj)
  c('rzp', msg);

  if (window.opener && typeof window.opener.postMessage == 'function'){
    window.opener.postMessage(msg, '*');
  }
}
</script>
</body>
</html>