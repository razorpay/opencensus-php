<!doctype html>
<html>
<?php
require('../scripts/sanitizeParams.php');

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = $_GET['key'] ?? 'rzp_test_1DP5mmOlF5G5ag';
$secret = 'thisissupersecret';

$public_url = $baseurl;
$private_url = $key_id.':'.$secret.'@'.$baseurl;
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
    <input type="hidden" name="method" value="aeps" />
    <input type="hidden" name="currency" value="INR" />
    <input type="hidden" value="<?=$key_id?>" name="key_id">
    <tr>
        <td colspan="40">Bank: </td>
        <td>
            <select name="bank">
                <option value="BARB">Bank of Baroda</option>
                <option value="HDFC">HDFC Bank</option>
                <option value="CORP">Corporation Bank</option>
                <option value="SBIN">SBI Bank</option>
                <option value="ICIC">ICICI Bank</option>
                <option value="AIRP">Airtel Payments Bank</option>
                <option value="FDRL">Federal Bank</option>
                <option value="RATN">RBL Bank</option>
                <option value="CITI">CITI Bank</option>
                <option value="UTIB">Axis Bank</option>
                <option value="YESB">Yes Bank</option>
                <option value="KKBK">Kotak Bank</option>
                <option value="VIJB">Vijaya Bank</option>
                <option value="PUNB">Punjab Bank</option>
                <option value="SBTR">State Bank of Travancore</option>
                <option value="SBBJ">State Bank of Bikaner and Jaipur</option>
                <option value="UBIN">United Bank</option>
                <option value="BARB">Bank of Baroda</option>
                <option value="INDB">Indusind Bank</option>
                <option value="PUNB">Punjab National Bank</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan='40'>Amount:</td>
        <td><input type="text" name="amount" size="25" value="100"></td>
    </tr>

    <tr>
        <td colspan="40">Aadhaar fingerprint: </b> </td>
        <td><input type="text" name="aadhaar[fingerprint]" value="sample fingerprint data"></td>
    </tr>

    <tr>
        <td colspan="40">Aadhaar session key: </b> </td>
        <td><textarea name="aadhaar[session_key]">abcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefghabcdefgh</textarea></td>
    </tr>

    <tr>
        <td colspan="40">Aadhaar hmac: </b> </td>
        <td><input type="text" name="aadhaar[hmac]" value="smplhmacsmplhmacsmplhmacsmplhmacsmplhmacsmplhmacsmplhmacsmplhmac"></td>
    </tr>

    <tr>
        <td colspan="40">Aadhaar number: </b> </td>
        <td><input type="text" name="aadhaar[number]" value="123456789012"></td>
    </tr>

    <tr>
        <td colspan="40">Aadhaar cert expiry: </b> </td>
        <td><input type="text" name="aadhaar[cert_expiry]" value="20191230"></td>
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
<h3>Test Capture/Refund</h3>
<div style="max-width: 400px; margin: 0 auto">
<form name ="capture" method="post" action="//<?=$private_url?>/payments/">
<input type="text" id="capture_id" placeholder = "Enter payment id to capture"/>
<input type = "text" id = "amount" name = "amount" placeholder = "Enter amount to capture" value="100" />
<select name="currency">
    <option value="INR">Indian Rupee</option>
    <option value="USD">US Dollar</option>
</select>
<input type="submit" value="Capture" onClick="javascript:document.capture.action = document.capture.action + document.getElementById('capture_id').value +'/capture'; document.capture.submit(); return false;"/>
</form>

<form name ="refund" method="post" action="//<?=$private_url?>/payments/">
<input type="text" id="refund_id" placeholder="Enter payment id to refund"/>
<input type="text" id="amount" name="amount" value="100"/>
<input type="submit" value="Refund" onClick="javascript:document.refund.action = document.refund.action + document.getElementById('refund_id').value +'/refund'; document.refund.submit(); return false;"/>
</form>
</div>
</div>
</body>
</html>
