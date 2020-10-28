<?php

require('vars.php');

?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="utf-8">
</head>
<body>
    <form action="merchant/charge.php" method="POST">
      <script
        src="<?= $checkout ?>/v1/checkout.js"
        data-key="rzp_test_1DP5mmOlF5G5ag"
        data-amount="5100"
        data-name="Daft Punk"
        data-description="Purchase Description"
        data-image="merchant/vk.jpg"
        data-netbanking="true"
        data-description="Tron Legacy"
        data-protocol="<?= $protocol ?>"
        data-hostname="<?= $hostname ?>"
        data-prefill.name="QA Razorpay"
        data-prefill.email="qa.testing@razorpay.com"
        data-prefill.contact="9999999999">
      </script>
    </form>
</body>
</html>
