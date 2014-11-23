<?php
error_reporting(E_ALL);
ini_set('display_errors','1');

require('../../../vendor/autoload.php');//Load API

use Razorpay\Api\Api;
require('config.php'); // Load API Credentials

$configDev = '../config_dev.php';
if (file_exists($configDev))
{
    require('../config_dev.php');
    Api::$baseUrl = $baseUrl;
}

$api = new Api(RZP_KEY_ID, RZP_KEY_SECRET);

if (isset($_POST['id']) === false)
{
    die("Payment id not provided");
}

$id = $_POST['id'];
$amount = $_POST['amount'];

$payment = $api->payment->fetch($id);

echo json_encode($payment->toArray());
