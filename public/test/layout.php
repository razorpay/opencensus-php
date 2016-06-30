<!doctype html>
<html style="background: #f6f6f6;">
<head>
  <title>Razorpay - Checkout Testing page</title>
</head>
<body style="width: 80%; max-width: 800px; margin: 30px auto; font-family: ubuntu, helvetica">
<script>
var options;
<?php
if ($baseurl !== "https://api.razorpay.com") {
?>
var Razorpay = {
  config: {
    api: "/"
  }
}
<?php
}
?>
</script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<textarea style="border-radius: 3px; width: 100%; display: block; height: 580px; font-family: mono; font-color: #444; resize: none;">
{
  "key": "rzp_test_1DP5mmOlF5G5ag",
  "amount": 600000,
  "name": "Innerchef",
  "description": "JEE Main & Advanced",
  "image": "http://i.imgur.com/n5tjHFD.png",
  "method": {
    "netbanking": true,
    "card": true
  },
  "remember_customer": true,
  "prefill": {
    "name": "Harshil \"Mathur",
    "email": "harshil@razorpay.com",
    "contact": "+918879524924",
    "card[number]": "4111 1111 1111 1111",
    "card[expiry]": "11 / 23"
  },
  "theme": {
    "color": "#1abc9c"
  },
  "handler": function(resp){
    alert(resp.razorpay_payment_id);
  }
}
</textarea>
<button style="font-size: 16px; font-family: inherit; padding: .5em 1em; color: #444;
  border: 1px solid #999; background-color: #E6E6E6; text-decoration: none;
  display: block; margin: 20px auto; border-radius: 2px;"
  onclick="eval('options = ' + document.querySelector('textarea').value); Razorpay.open(options); return false;">Open</button>
</body>
</html>
