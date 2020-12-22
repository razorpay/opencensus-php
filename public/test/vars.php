<?php
require('../scripts/sanitizeParams.php');

$checkout = 'https://checkout.razorpay.com';
$fonts = 'https://s3.amazonaws.com/checkout-live/lato';

$protocol = 'https';

$hostname = $_SERVER['HTTP_HOST'];

if ($hostname === 'beta.razorpay.com')
{
    $checkout = 'https://beta-checkout.razorpay.com';
    $fonts = 'https://s3.amazonaws.com/checkout-beta/lato';
}

$configFile = __DIR__ . '/config.php';

if (file_exists($configFile))
{
    require($configFile);
}

$domain = $protocol.'://'.$hostname;
