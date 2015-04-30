<?php

$checkout = 'http://localhost:9000/dist';

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

$configFile = __DIR__ . '/config.php';

if (file_exists($configFile))
{
    require($configFile);
}
