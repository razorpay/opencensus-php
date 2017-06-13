<?php

require('../vars.php');

require('../../../vendor/autoload.php');//Load API

use Razorpay\Api\Api;

require('config.php'); // Load API Credentials

$baseUrl = $protocol . '://' . $hostname . '/v1/';

Api::setBaseUrl($baseUrl);

$api = new Api(RZP_KEY_ID, RZP_KEY_SECRET);

if (isset($_POST['razorpay_payment_id']) === false)
{
    die("Payment id not provided");
}

$id = $_POST['razorpay_payment_id'];

$payment = $api->payment->fetch($id);

echo json_encode($payment->toArray());
