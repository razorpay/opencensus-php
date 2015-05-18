<?php

$checkout = 'https://checkout.razorpay.com';

$protocol = 'https';

$hostname = $_SERVER['HTTP_HOST'];

if ($hostname === 'beta.razorpay.com')
{
    $checkout = 'https://betacheckout.razorpay.com';
}

$configFile = __DIR__ . '/config.php';

if (file_exists($configFile))
{
    require($configFile);
}
