<?php
require('../scripts/sanitizeParams.php');

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';
$baseurl = 'api.razorpay.com' . '/v1';

$key_id = 'rzp_live_ILgsfZCZoFIKMb';
$secret = 'thisissupersecret';

$public_url = $key_id.'@'.$baseurl;
$private_url = $key_id.':'.$secret.'@'.$baseurl;
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
                <option value="card" selected>Card</option>
                <option value="wallet">Wallet</option>
                <option value="emi">Emi</option>
                <option value="upi">UPI</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Bank (Net Banking): </td>
        <td>
            <select name="bank">
                <option value="HDFC">HDFC Bank</option>
                <option value="SBIN">SBI Bank</option>
                <option value="ICIC">ICICI Bank</option>
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
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Wallet </td>
        <td>
            <select name="wallet">
                <option value="paytm">Paytm</option>
                <option value="mobikwik">Mobikwik</option>
                <option value="payzapp">Payzapp</option>
                <option value="payumoney">Payumoney</option>
                <option value="jiomoney">Jiomoney</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select EMI Duration</td>
        <td>
            <select name="emi_duration">
                <option value="3">3 Months @12%</option>
                <option value="6">6 Months @12%</option>
                <option value="9">9 Months @14%</option>
                <option value="12">12 Months @14%</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan='40'>CardHolder/Member Name:</td>
        <td><input type="text" name="card[name]" size="25" value="shashank"></td>
        <input type="hidden" value="INR" name="currency">
        <input type="hidden" value="<?=$key_id?>" name="key_id">
    </tr>
    <tr>
        <td colspan="40">Card No: </b> </td>
        <td><input type="text" name="card[number]" value="4012001038443335" size="25"></td>
    </tr>
    <tr>
        <td colspan="40">CVV:</td>
        <td><input size="3" type="text" name="card[cvv]" value="880" maxlength=4></td>
    </tr>
    <tr>
        <td colspan ='40'>Exp Date:</td>
        <td><input type="text" name="card[expiry_month]" value="11"></td>
        <td><input type="text" name="card[expiry_year]" value="2020"></td>
        <tr>
            <td colspan='40'>Amount:</td>
            <td><input type="text" name="amount" size="25" value="100"></td>
    </tr>
    <tr>
        <td colspan='40'>Email:</td>
        <td><input type="text" name="email" size="25" value="test@razorpay.com"></td>
        <td><input type="text" name="contact" size="25" value="1234567890"></td>
    </tr>
    <tr>
        <td colspan='40'>Order Id:</td>
        <td><input type="text" name="notes[order_id]" size="25" value="3453"></td>
    </tr>
    <tr>
        <td colspan='40'>VPA:</td>
        <td><input type="text" name="vpa" size="25" value="razorpay@ybl"></td>
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
<div style="text-align:center">
<h3>Test Capture/Refund</h3>

</div>

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
