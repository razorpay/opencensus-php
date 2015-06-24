<?php
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
?>
<!doctype html><head><title>Razorpay - Payment in progress</title><meta charset="UTF-8"></head>
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
  body{ text-align: center; color: #444; font-family: 'lato', sans-serif; font-size: 16px; line-height: 30px; white-space: nowrap;}
  .middlechild,.container{display: inline-block; vertical-align: middle; white-space: normal;}
  .middlechild{width: 1px; height: 90%;}
  .container{width: 80%; max-width: 900px; margin: 150px auto;}
  #logo{padding-bottom: 20px;}
  #top{position: absolute; top: 20px; text-align: center; border-bottom: 2px solid #ddd; width: 80%; left: 10%;}
  .heading{text-transform: uppercase; font-size: 36px; letter-spacing: 1px}
  #pro{border-radius: 6px; margin-top: 20px;}
  .powered-by{font-size: 50px;text-decoration: none; color: #999; margin-top: 10px; display: block; line-height: 60px}
  img{max-width: 100%; display: block; margin: 10px auto;}
  #description{clear:both; padding: 40px 0 10px; font-size: 22px; font-weight: bold;}
  #order{display: none; max-width: 600px; margin: 0 auto;}
  .amount{font-size: 50px; line-height: 60px; color: #29b3d2;}
</style>
<div class="middlechild"></div>
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
  <div style="margin-top: 30px">Redirecting to bank page...</div>
  <div class="powered-by">&#xe608;</div>
<div class="autosubmit">
  <form method="POST" action="{{=it.data.url}}" id="rzp-dcform">
    <input type="hidden" id="PaReq" name="PaReq" value="{{=it.data.PAReq}}">
    <input type="hidden" id="MD" name="MD" value="{{=it.data.paymentid}}">
    <input type="hidden" id="TermUrl" name="TermUrl" value="{{=it.callbackUrl}}">
  </form>
</div>
</div>

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
<style>@font-face{font-family:'lato';src:url("/fonts/lato.eot?#iefix") format('embedded-opentype'),url("/fonts/lato.woff") format('woff'),url("/fonts/lato.ttf") format('truetype'),url("/fonts/lato.svg#lato") format('svg');font-weight:normal;font-style:normal}i{font-size:24px;font-style:normal}</style>
</body>
</html>