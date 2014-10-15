<?php
error_reporting(E_ALL);
ini_set('display_errors','1');

require('../../../vendor/autoload.php');//Load API

use Razorpay\Api\Api;
require('config.php');//Load API Credentials

$api = new Api(RZP_KEY_ID,RZP_KEY_SECRET);

if(!isset($_POST['id'])) die("Payment id required");

$id = $_POST['id'];
$amount = $_POST['amount'];

$payment = $api->payment->get($id);

if (($amount === $payment->amount) and
    ($payment->error_code === null) and
    ($payment->status === 'authorized'))
{
	//
    // Payment was successful
	// Do your server side handling
    //

    echo json_encode($payment);
}
else
{
	die("There was an error in processing your request");
}