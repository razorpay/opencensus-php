<?php
require('../scripts/sanitizeParams.php');

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = 'rzp_test_1DP5mmOlF5G5ag';
$secret = 'thisissupersecret';

$public_url = $key_id.'@'.$baseurl;
$private_url = $key_id.':'.$secret.'@'.$baseurl;
$callback_url = 'http://'.$baseurl.'/return/callback?key_id='.$key_id;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//Dtd HTML 4.0 transitional//EN">
<html>
<head>
    <title>Razorpay - Testing page</title>
</head>

<body>
<table border="1" align="center"  width="100%" >
    <tr>
    <td align = "left" width = "90%"><b>Testing Page</b></td>
    <td align = "right"width = "10%"><img src="" width="169" height="37" border="0" alt=""></td>
    </tr>
</table>
<br><br>
<form method="post" id="paymentform" action="//<?=$public_url?>/payments">
<table border="1" align="center"  width="300">
    <tr>
    <th colspan="50" bgcolor="brown" >Enter Parameters</th>
    </tr>
    <tr>
        <td colspan="40">Select Method: </td>
        <td>
            <select name="method">
                <option value="netbanking">Net Banking</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Bank (Net Banking): </td>
        <td>
            <select name="bank">
                <option value="KKBK">Kotak Bank</option>
            </select>
        </td>
    </tr>
    <tr style="display:none">
        <td colspan="40">Select Wallet </td>
        <td>
            <select name="wallet">
                <option value="paytm">Paytm</option>
                <option value="mobikwik">Mobikwik</option>
            </select>
        </td>
    </tr>
    <tr style="display:none">
        <td colspan='40'>CardHolder/Member Name:</td>
        <td><input type="text" name="card[name]" size="25" value="shashank"></td>
        <!-- <td><input type="text" name="callback_url" value="<?= $callback_url ?>"></td> -->
        <input type="hidden" value="INR" name="currency">
        <input type="hidden" value="<?=$key_id?>" name="key_id">
    </tr>
    <tr style="display:none">
        <td colspan="40">Card No: </b> </td>
        <td><input type="text" name="card[number]" value="4012001038443335" size="25"></td>
    </tr>
    <tr style="display:none">
        <td colspan="40">CVV:</td>
        <td><input size="3" type="text" name="card[cvv]" value="880" maxlength=4></td>
    </tr>
    <tr style="display:none">
        <td colspan ='40'>Exp Date:</td>
        <td><input type="text" name="card[expiry_month]" value="11"></td>
        <td><input type="text" name="card[expiry_year]" value="2015"></td>
        <tr>
            <td colspan='40'>Amount:</td>
            <td><input type="text" name="amount" size="25" value="500"></td>
    </tr>
    <tr style="display:none">
        <td colspan='40'>Email:</td>
        <td><input type="text" name="email" size="25" value="nemo+test@razorpay.com"></td>
        <td><input type="text" name="contact" size="25" value="1234567890"></td>
    </tr>
    </tr>
    <tr>
        <td colspan="100" align="center"><input type="submit" value="  Submit  "></td>
    </tr>
    <tr>
        <th colspan="50" bgcolor="brown" height="15"></th>
    </tr>
</form>
</table>
<br><br>

<table width="96%" border="0" cellspacing="0" cellpadding="0">
<tr>
    <td height="2" bgcolor="black" class="titleline"></td>
</tr>
</table>
<table border="1" align="center"  width="100%" >
    <tr>
    <td align="left" width="90%"><b>Testing Page</b></td>
    <td align="right"width="10%"><img src="" width="169" height="37" border="0" alt=""></td>
    </tr>
</table>

</body>
</html>
