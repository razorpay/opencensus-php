<?php
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
?>
<!doctype html>
<head>
    <title>Razorpay - Payment in progress</title>
    <meta charset="UTF-8">
</head>
<body>
<script>

function autosubmit(data){
  document.querySelectorAll('input[name="PaReq"]')[0].setAttribute('value', data.PAReq);
  document.querySelectorAll('input[name="MD"]')[0].setAttribute('value', data.paymentid);
  document.querySelectorAll('input[name="TermUrl"]')[0].setAttribute('value', data.callbackUrl);
  document.querySelectorAll('#rzp-dcform')[0].setAttribute('action', data.url);
  document.getElementById('rzp-dcform').submit();
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

if(!window.CheckoutBridge){
  if (window.addEventListener) {
    var callback = function(message){
      handleMessage(message.data);
    }
    window.addEventListener('message', callback, false);
  }
  else {
    window.attachEvent('onmessage', callback);
  }
  var intervalID = setInterval(function(){
    receive_cookie = readCookie('rzp-receive')
    if(receive_cookie){
      handleMessage(JSON.parse(receive_cookie));
      c('rzp-receive', '', -1)
    }
  }, 500)
}

function handleMessage(data){
  if(typeof data == 'string'){
    data = JSON.parse(data);
  }
  if(typeof data.rzp !== 'undefined'){
    if(typeof data.location !== 'undefined'){
      window.location = data.location;
    }
    else if(typeof data.autosubmit !== 'undefined'){
      autosubmit(data.autosubmit);
    }
  }
}

</script>
<style>
  .rzp-loader {
    display: block;
    position: absolute;
    left: 50%;
    top: 50%;
    width: 140px;
    height: 140px;
    margin: -70px 0 0 -70px;
    border-radius: 50%;
    border: 5px solid transparent;
    border-top-color: #29b7d6;
    -webkit-animation: spin 2s linear infinite;
    animation: spin 2s linear infinite;
  }
  .rzp-loader:before {
    content: "";
    position: absolute;
    top: 5px;
    left: 5px;
    right: 5px;
    bottom: 5px;
    border-radius: 50%;
    border: 5px solid transparent;
    border-top-color: #0b81b2;
    -webkit-animation: spin 3s linear infinite;
    animation: spin 3s linear infinite;
    -webkit-transform: rotate(45deg);
    -moz-transform: rotate(45deg);
    -ms-transform: rotate(45deg);
    transform: rotate(45deg);
  }
  .rzp-loader:after {
    content: "";
    position: absolute;
    top: 15px;
    left: 15px;
    right: 15px;
    bottom: 15px;
    border-radius: 50%;
    border: 5px solid transparent;
    border-top-color: #29b7d6;
    -webkit-animation: spin 1.5s linear infinite;
    animation: spin 1.5s linear infinite;
    -webkit-transform: rotate(90deg);
    -moz-transform: rotate(90deg);
    -ms-transform: rotate(90deg);
    transform: rotate(90deg);
  }

  .rzp-logo {
    display: block;
    position: absolute;
    left: 50%;
    top: 50%;
    width: 80px;
    height: 80px;
    margin-top: -40px;
    margin-left: -40px;
  }

  @-webkit-keyframes spin {
    0%{
      -webkit-transform: rotate(0deg);
      transform: rotate(0deg);
    }
    100% {
      -webkit-transform: rotate(360deg);
      transform: rotate(360deg);
    }
  }
  @-ms-keyframes spin {
    0%{
      -ms-transform: rotate(0deg);
      transform: rotate(0deg);
    }
    100% {
      -ms-transform: rotate(360deg);
      transform: rotate(360deg);
    }
  }
  @-moz-keyframes spin {
    0%{
      -moz-transform: rotate(0deg);
      transform: rotate(0deg);
    }
    100% {
      -moz-transform: rotate(360deg);
      transform: rotate(360deg);
    }
  }
  @keyframes spin {
    0%{
      -webkit-transform: rotate(0deg);
      -moz-transform: rotate(0deg);
      -ms-transform: rotate(0deg);
      transform: rotate(0deg);
    }
    100%{
      -webkit-transform: rotate(360deg);
      -moz-transform: rotate(360deg);
      -ms-transform: rotate(360deg);
      transform: rotate(360deg);
    }
  }
</style>

<img class="rzp-logo" alt="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAH0AAAB9CAYAAACPgGwlAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABFZJREFUeNrsnM1OE1EYhsfa8GNTfqKIcVWWuqp30K273gG9A7kD9QqACzC0VwCuXMLGNSQmuoFIJDEtoaGlIEgKcc7kIKVMe07LEM7P8yQ4BGeamb55v/neOedMEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACAEbz68n3KpPNJIcm9C14MNyuI7o/gi+FmNfz5bNJ5PUKaexE7L90ttrs/3r6ew+luC74Qbtal4IKKaeeYRqZEmzXh7mLXfy0hupuCF+S9u7tLL4elvWHa+VLe7y74B1nO42LZsonnjNOHFzsn3Z3vsctG6PItE88dpw8neCncbPYR3MgGjsiWbLPWjXExDacP36xtaghutMsRffBmLad5SNnk66GRUzdropwXBjhMxLRdnG6n4EVZzgsDHlox/dpo5OKbNTFQUhri8K3Q5W9Mv0bK+03B8zJ754b8iGUbrhOnXwu+IB0+LI3Q5dM2XGsasaNyvjrEvdtKl3vfyMlm7WcCghsf0xD9OnvHjYwNJbjpMc170WX+fp/gR1Zsun5fnV5K8LNETNtAdPN5p9qhWWsG7fO2Uw2ct927HBbteR+/vLgMatu16PfJ2UmdmFZGdItdftY6C6rb1Uj4mbkZpzp2b0WXT9xiJz7U9+pRSY++lNF0kH2adbK0++j0Wy4X923h7vM/5///pin4mk0xzUvR5ZO3G117q94K6r/qUTnvJPvMXZf75vSFzmZNlPPWQevWTkLw9Ijya9m1Lab5Kvq8+EeU8epONWj/jY9jmqX9o81fhBeiy5iWE42acHgvRp6MBGPZMWVME/dzRDeci/bF/P7OfnDaOu27n0Yuj2KaiatWEL2DF5++Fva+7RW6m7VuUo9TTse0G9fqeFlfDCPZukrwAVxubUxz3ulXS45CsfMnhydax7ge05x2eueSIxHJdFzuQ0xz0ulxs1ib+009l3sQ05wTvet1HxFi8KRXFvcxpjlV3uUs1lsrSK8GTxJs4Bo43YxyHruCVAyinDTUDZyIaZmpjFel3VrR+7zuYyCXiwZOCK9gw4WYZnV5V7zuI+rW4wZS7lDalwPHSFsm+EqgmNQoyrpOTBNlXTOmrbkmesoiwacCjRcCJNzAOedy28p7KVAsTBAxrXMGTM/yNprWjWllRH9YlNOWxUwYHaZfaq0zdCqmWSe67NZz/fYRMU2ngfM1ptnodLXLNTt2X2OaVaLLETMaOM+crnUvJ6Y5InrctOU4jg+OcblDTi+qYpqIaKq5bwPEtMDVmGaT6OrVpZpj5pPP/Zj0aLXoMqb1e+Gu9nP2aNKjR9OhbHb6fFIde2Y6oxvTthD9YWOasoFL+AlcJfAEU52uFFyMpulMhxrPjuvGtDKiG17aj2pHWh80MTuBy00X/WrdWb99xHN23Zim+Zx9CdENd/nh70NiGgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA980+AAQC3oqOf0G53LwAAAABJRU5ErkJggg=="/>

<div class="rzp-loader"></div>

<div class="autosubmit">
  <form method = "POST" action = "{{=it.data.url}}" id = "rzp-dcform">
    <input type = "hidden" name = "PaReq" value = "{{=it.data.PAReq}}">
    <input type = "hidden" name = "MD" value = "{{=it.data.paymentid}}">
    <input type = "hidden" name = "TermUrl" value = "{{=it.callbackUrl}}">
    <input style = "display:none" type = "submit" value = "Submit">
  </form>
</div>

<script>
if(!window.CheckoutBridge){
  var msg = {
    source: 'popup',
    loaded: true
  }
  c('rzp', JSON.stringify(msg));
  if(window.opener && typeof window.opener.postMessage == 'function'){
    window.opener.postMessage(msg, '*');
  }
}
</script>
</body>
</html>