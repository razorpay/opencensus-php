<?php

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = 'rzp_live_ILgsfZCZoFIKMb';
$secret = 'thisissupersecret';

$public_url = $key_id.'@'.$baseurl;
$private_url = $key_id.':'.$secret.'@'.$baseurl;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//Dtd HTML 4.0 transitional//EN">
<HTML>
<HEAD>
    <TITLE>Razorpay - Testing page</TITLE>
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
<table border="1" align="center"  width="300">
    <tr>
    <th colspan="50" bgcolor="brown" ><font  size = 2 color = White face = verdana >Enter Parameters</th>
    </tr>
            <input name="method" type="hidden" value="card">
<!--     <tr>
        <td colspan="40">Select Method: </td>
        <td>
            <select name="method">
                <option value="netbanking">Net Banking</option>
                <option value="card" selected>Card</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Bank (Net Banking): </td>
        <td>
            <select name="bank">
                <option value="HDFC">HDFC Bank</option>
            </select>
        </td>
    </tr>
 -->    <tr>
        <td colspan="40">Card No: </b> </td>
        <td><input type="text" name="card[number]" value="" size="25"></td>
    </tr>
    <tr>
        <td colspan="40">CVV:</td>
        <td><input size="3" type="text" name="card[cvv]" value="" maxlength=4></td>
    </tr>
    <tr>
        <td colspan ='40'>Exp Date:</td>
        <td><input type="text" name="card[expiry_month]" value="" placeholder="Month"></td>
        <td><input type="text" name="card[expiry_year]" value="" placeholder="Year"></td>
        <tr>
            <td colspan='40'>Amount:</td>
            <input type="hidden" name="amount" size="25" value="5000">
            <td>50</td>
        </tr>
        <tr>
            <td colspan='40'>CardHolder/Member Name:</td>
            <td><input type="text" name="card[name]" size="25" value=""></td>
        </tr>
        <tr>
            <td>Email:</td>
            <td><input type="text" name="email" size="25" value="" placeholder="email"></td>
        </tr>
        <tr>
            <td>Mobile:</td>
            <td><input type="text" name="contact" size="25" value="" placeholder="contact"></td>
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

</div>

<table width="96%" border="0" cellspacing="0" cellpadding="0">
<tr>
    <td height="2" bgcolor="black" class="titleline"></td>
</tr>
</table>
<table border="1" align="center"  width="100%" >
    <tr>
    <td align="left" width="90%"><font  size = 5 color = darkblue face = verdana ><b>Testing Page</td>
    <td align="right"width="10%"><IMG SRC="" WIDTH="169" HEIGHT="37" BORDER="0" ALT=""></td>
    </tr>
</table>

</BODY>
</HTML>