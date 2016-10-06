<!doctype html>
<html style="background: #f6f6f6;">
<head>
  <title>Razorpay - Checkout Testing page</title>
</head>
<body style="width: 80%; max-width: 500px; margin: 30px auto; font-family: ubuntu, helvetica">
<textarea style="border-radius: 3px; width: 100%; display: block; height: 400px; font-family: mono; font-color: #444; resize: none;">
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
<script>
var options;
var textarea = document.querySelector('textarea');
try {
  textarea.focus();
  saved = localStorage.getItem('options');
  if (saved) {
    textarea.value = saved;
  }
} catch(e){}

function setOptions(){
  try {
    eval('options = ' + textarea.value);
    localStorage.setItem('options', textarea.value);
  } catch(err){
    console.log(err.message);
  }
}

textarea.oninput = setOptions;
setOptions();

textarea.onkeydown = function(e){
  if (e.which == 13 && e.ctrlKey) {
    document.querySelector('button').click();
  }
  if (e.which == 9) {
    e.preventDefault();
    var selStart = this.value.slice(0, this.selectionStart);
    this.value = selStart + '  ' + this.value.slice(this.selectionEnd);
    this.selectionEnd = this.selectionStart = selStart.length + 2;
  }
}

<?php
$checkout_host = 'https://checkout.razorpay.com';

if ($_SERVER['HTTP_HOST'] !== "https://api.razorpay.com") {
  $checkout_host = 'https://betacheckout.razorpay.com/';
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
<script src="<?= $checkout_host ?>/v1/checkout.js"></script>
<button style="font-size: 16px; font-family: inherit; padding: .5em 1em; color: #444;
  border: 1px solid #999; background-color: #E6E6E6; text-decoration: none;
  display: block; margin: 20px auto; border-radius: 2px;"
  onclick="Razorpay.open(options);return false;">Open</button>
</body>
</html>
