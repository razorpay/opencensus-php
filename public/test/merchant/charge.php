<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require('../../../vendor/autoload.php');//Load API

use Razorpay\Api\Api;
require('config.php');//Load API Credentials

$api = new Api(RZP_KEY_ID,RZP_KEY_SECRET);

if(!isset($_POST['id'])) die("Transaction id required");

$id = $_POST['id'];
$amount = $_POST['amount'];

$transaction = $api->transaction->get($id);

if($amount === $transaction->amount && $transaction->error_code === null && $transaction->status === 'auth')
{
	//Transaction was successful
	//Do your server side handling
	echo json_encode($transaction);
}
else
{
	die("There was an error in processing your request");
}