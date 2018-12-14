<!doctype html>
<html>
<?php

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
<script type="application/javascript">
    function disableEmptyInputs(form) {
        var controls = form.elements;
        for (var i=0, iLen=controls.length; i<iLen; i++) {
            controls[i].disabled = controls[i].value == '';
        }
    }
</script>
<form method="post" id="paymentform" action="//<?=$public_url?>/payments" onsubmit="disableEmptyInputs(this)">
    <div style="background: brown; color: #fff; text-align: center; padding: 8px 0">Enter Parameters</div>
    <table>
        <tr>
            <input type="hidden" value="card" name="method">

        </tr>
        <tr>
            <td colspan="40">Razorpay Key:</td>
            <td>
                <input type="text" value="<?=$key_id?>" name="key_id">
            </td>
        </tr>
        <tr>
            <td colspan='40'>Card Holder Name:</td>
            <td><input type="text" name="card[name]" size="25" value="User Name"></td>
            <!-- <td><input type="text" name="callback_url" value="<?= $callback_url ?>"></td> -->
            <td><input type="hidden" value="INR" name="currency"></td>
        </tr>
        <tr>
            <td colspan="40"><b>Card No: </b> </td>
            <td><input type="text" name="card[number]" value="6074819900004939" size="25"></td>
        </tr>
        <tr>
            <td colspan="40">CVV:</td>
            <td><input size="3" type="text" name="card[cvv]" value="123" maxlength=4></td>
        </tr>
        <tr>
            <td colspan ='40'>Exp Date:</td>
            <td><input type="text" name="card[expiry_month]" value="11"></td>
            <td><input type="text" name="card[expiry_year]" value="2020"></td>
        <tr>
            <td colspan='40'>Amount:</td>
            <td><input type="text" name="amount" size="25" value="100"></td>
            <td>
                <select name="currency">
                    <option value="INR">Indian Rupee</option>
                    <option value="USD">US Dollar</option>
                    <option value="EUR">Euro</option>
                    <option value="SGD">Singapore Dollar</option>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan='40'>Email:</td>
            <td><input type="text" name="email" size="25" value="test@razorpay.com"></td>
            <td><input type="text" name="contact" size="25" value="9976543210"></td>
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
</body>
</html>
