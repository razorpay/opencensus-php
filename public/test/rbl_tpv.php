<!doctype html>
<html>
<?php
require('../scripts/sanitizeParams.php');

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = $_GET['key'] ?? 'rzp_live_4dngATlGkC5Wap';
$secret = 'XvLXaKrJf5iSyY8N0QuwTAcJ';

$private_url = $key_id.':'.$secret.'@'.$baseurl;

$public_url = $baseurl;
$callback_url = 'http://'.$baseurl.'/return/callback?key_id='.$key_id;
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Razorpay - Testing page</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Droid Sans', 'Helvetica Neue', sans-serif;
        }
        form {
            margin: 20px auto;
            max-width: 700px;
        }
        input[type=submit] {
            color: #414141;
            border: 1px solid #ccc;
            background-color: #E6E6E6;
            text-decoration: none;
            border-radius: 2px;
            padding: 10px 20px;
            text-transform: uppercase;
            margin: 10px 0;
        }
        input[type=text], select {
            width: 100%;
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
            outline: none;
            border: 1px solid #ccc;
            border-radius: 2px;
            background: none;
            line-height: 16px;
            padding: 6px 12px;
            background: #fff;
        }
        input[type=checkbox] {
            width: 20px;
            height: 20px;
            margin: 0;
            vertical-align: middle;
            margin-right: 4px;
        }
        table {
            line-height: 36px;
            font-size: 14px;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background: #fafafa;
            padding: 10px 20px;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<form method="post" id="paymentform" action="//<?=$public_url?>/payments">
<div style="background: brown; color: #fff; text-align: center; padding: 8px 0">Enter Parameters</div>
<table>
    <tr>
        <td colspan="40">Select Method: </td>
        <td>
            <select name="method">
                <option value="netbanking">Net Banking</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Bank (Netbanking): </td>
        <td>
            <select name="bank">
                <option value="RATN">RBL Bank</option>
            </select>
        </td>
    </tr>
     <tr>
        <td><input type="hidden" name="order_id" size="25" value="order_8zjHebCLR5K9pZ"></td>
    </tr>


        <input type="hidden" value="INR" name="currency">
        <input type="hidden" value="<?=$key_id?>" name="key_id">


        <tr>
            <td><input type="hidden" name="amount" size="25" value="100"></td>
            <td><input type="hidden" name="currency" size="25" value="INR"></td>
        </td>


    <tr>
        <td colspan='40'>Email:</td>
        <td><input type="text" name="email" size="25" value="test@razorpay.com"></td>
        <td><input type="text" name="contact" size="25" value="9876543210"></td>
    </tr>
    <tr>
        <td colspan="100" align="center">
            <input type="submit" value="  Submit  " >
        </td>
    </tr>
</table>
<div style="background: brown; color: #fff; text-align: center; height: 20px"></div>
</form>

<br><br>
<div style="text-align:center">
</div>
</div>
</body>
</html>
