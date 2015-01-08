<?php

require('../vars.php');

require('../../../vendor/autoload.php');//Load API

use Razorpay\Api\Api;

require('config.php'); // Load API Credentials

$baseUrl = $protocol . '://' . $hostname . '/v1/';

Api::$baseUrl = $baseUrl;

$api = new Api(RZP_KEY_ID, RZP_KEY_SECRET);

if (isset($_POST['id']) === false)
{
    die("Payment id not provided");
}

$id = $_POST['id'];
$amount = $_POST['amount'];

$payment = $api->payment->fetch($id);

echo json_encode($payment->toArray());
