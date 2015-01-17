<?php

$checkout = 'https://checkout.razorpay.com';

$protocol = 'http';

if ((isset($_SERVER['HTTPS'])) and
    ($_SERVER['HTTPS'] === 'on'))
{
    $protocol = 'https';
}

$hostname = $_SERVER['HTTP_HOST'];

if ($hostname === 'beta.razorpay.com')
{
    $checkout = 'https://betacheckout.razorpay.com';
}

if (file_exists(__DIR__ . 'config.php'))
{
    require(__DIR__ . 'config.php');
}
