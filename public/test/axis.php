<?php

require('vars.php');

$key = $_GET['key'] ?? 'rzp_test_1DP5mmOlF5G5ag';
?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="utf-8">
</head>
<body>
    <script>
        var Razorpay = {
          config: {
            api: '/'
          }
        };
    </script>
    <form action="merchant/charge.php" method="POST">
      <script
        src="<?= $checkout ?>/v1/checkout.js"
        data-key="<?= $key ?>"
        data-amount="100"
        data-name="Axis Corporate test"
        data-description="Test for axis corporate"
        data-netbanking="true"
        data-protocol="<?= $protocol ?>"
        data-hostname="<?= $hostname ?>"
        data-method.card="false"
        data-method.upi="false"
        data-method.wallet="false"
        data-prefill.email="test@test.com"
        data-prefill.contact="8888888888">
      </script>
    </form>
</body>
</html>
