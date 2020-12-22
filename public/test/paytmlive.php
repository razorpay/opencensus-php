<?php
require('../scripts/sanitizeParams.php');

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = 'rzp_live_ILgsfZCZoFIKMb';

$public_url = $key_id.'@'.$baseurl;
$private_url = $key_id.':'.$secret.'@'.$baseurl;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//Dtd HTML 4.0 transitional//EN">
<HTML>
<HEAD>
    <TITLE>Razorpay Paytm - Testing page</TITLE>
</HEAD>

<BODY>
<table border="1" align="center"  width="100%" >
    <tr>
    <td align = "left" width = "90%"><font  size = 5 color = darkblue face = verdana ><b>Testing Page</b></td>
    <td align = "right"width = "10%"><img SRC="" WIDTH="169" HEIGHT="37" BORDER="0" ALT=""></td>
    </tr>
</table>
<br><br>
<form method="post" id="paymentform" action="//<?=$public_url?>/payments">
<table border="1" align="center"  width="500">
    <tr>
    <th colspan="50" bgcolor="brown" ><font  size = 2 color = White face = verdana >Enter Parameters</th>
    </tr>
    <tr>
        <td colspan="40">Select Method: </td>
        <td>
            <select name="method">
                <option value="netbanking">Net Banking</option>
                <option value="wallet">Wallet</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Wallet: </td>
        <td>
            <select name="wallet">
                <option value="paytm">Paytm</option>
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
        <tr>
            <td colspan='40'>Amount:</td>
            <td><input type="text" name="amount" size="25" value="500"></td>
        </tr>
        <tr>
            <td colspan='40'>Email:</td>
            <td><input type="text" name="email" size="25" value="qa.testing@razorpay.com"></td>
        </tr>
        <tr>
            <td colspan='40'>Contact:</td>
            <td><input type="text" name="contact" size="25" value="1234567890"></td>
            <input type="hidden" value="INR" name="currency">
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
<div style="text-align:center">
<h3>Test Capture/Refund</h3>

</BODY>
</HTML>
