<?php

error_reporting(E_ALL);
ini_set('display_errors','1');

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
    $checkout = 'https://beta.checkout.razorpay.com';
}

if (file_exists('config.php'))
{
    require('config.php');
}
