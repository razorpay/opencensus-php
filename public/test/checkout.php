<?php
error_reporting(E_ALL);
ini_set('display_errors','1');

$checkout = 'https://checkout.razorpay.com';
$protocol = 'https';
$hostname = 'api.razorpay.com';

if (file_exists('config.php'))
{
    require('config.php');
}

?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="utf-8">
    <link href="<?= $checkout ?>/v1/css/style.css" type="text/css" rel="stylesheet">
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
        data-protocol=<?= $protocol ?>
        data-hostname=<?= $hostname ?>
        data-prefill.name="Harshil Mathur"
        data-prefill.email="harshil@razorpay.com"
        data-prefill.contact="9999999999">
      </script>
    </form>
</body>
</html>