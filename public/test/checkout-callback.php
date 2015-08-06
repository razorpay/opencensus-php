<?php

require('vars.php');

if (isset($_POST['razorpay_payment_id']))
{
    echo 'Payment successfully authorized. Payment Id - ' . $_POST['razorpay_payment_id'];
    exit(0);
}
else if (isset($_POST['error']['code']))
{
    echo json_encode($_POST);
    exit(0);
}

?>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="<?= $checkout ?>/v1/checkout.js"></script>
</head>

<body style="text-align: center; color: #555; font-family: sans-serif">
    <h1>Demo Redirect Payment Page</h1>
    <button id="button">Pay ₹225</button>
    <script>
        var razorpay = new Razorpay({
          key: 'rzp_test_1DP5mmOlF5G5ag',
          protocol: '<?= $protocol ?>', // Only needed for internal tests
          hostname: '<?= $hostname ?>', // Only needed for internal tests
          callback_url: '<?= $domain ?>/test/checkout-callback.php', // Url where redirection will happen after the payment
          amount: '22500',
          name: 'Customer Name here',
          description: '', // purchase description to show below 'name'
          image: '', // Relative/absolute url of the image to be show on checkout form
          prefill: {
            email: 'Jane@Doe.com',
            name: 'Jane Doe',
            contact: '8877799990'
          }
        })
        document.getElementById('button').onclick = function(){
          razorpay.open();
        }

        // or you can do 'new Razorpay(options).open()'' to show form instantaneously
    </script>
</body>