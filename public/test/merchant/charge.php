<?php
use Razorpay\Api\Api;
var_dump($_POST);
/*
require('config.php');

use Razorpay\Api\Api;
$api = new Api(RZP_KEY_ID,RZP_KEY_SECRET);

if(!isset($_POST['id'])) die("Transaction id required");

$id = $_POST['id'];
$amount = $_POST['amount'];

$transaction = $api->transaction->get($id);

if($amount === $transaction->amount && $transaction->error === '' && $transaction->status === 'auth')
{
	//Transaction was successful
	//Do your server side handling
	$response = $api->transaction->capture($id);
	echo json_encode($response);
}
else
{
	die($transaction->error);
}
*/