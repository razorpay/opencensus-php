<!doctype html>
<head>
    <title>Razorpay - Payment in progress</title>
    <style>body{background:#fff; font-family: sans-serif;}</style>
</head>
<body>
<script>

function c(name, value, days){
  if (days) {
    var date = new Date();
    date.setTime(date.getTime()+(days*24*60*60*1000));
    var expires = "; expires="+date.toGMTString();
  }
  else var expires = "";
  document.cookie = name+"="+value+expires+"; path=/";
}

// Do not remove the below 'callback data' comments because they help
// during tests for extracting callback data from js

// Callback data //
var data = {{json_encode($data);}};
// Callback data //

if (window.CheckoutBridge) {
    if (typeof CheckoutBridge.oncomplete == 'function') {
        CheckoutBridge.oncomplete(JSON.stringify(data));
    }
} else {
    c('rzp', JSON.stringify(data));
    if (window.opener && typeof window.opener.postMessage == 'function') {
        window.opener.postMessage(data, '*');
    }
}
</script>

<pre>
<?php echo json_encode($data, JSON_PRETTY_PRINT); ?>
</pre>

<!--Your payment is currently in progress. Please wait.-->
</body>
</html>
