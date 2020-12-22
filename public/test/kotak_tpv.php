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
    <tr>
    <td colspan='40'>Order Id:</td>
    <td><input value="" name="order_id"></td>
    </tr>
    <tr style="display:none">
        <!-- <td><input type="text" name="callback_url" value="<?= $callback_url ?>"></td> -->
        <input type="hidden" value="INR" name="currency">
        <input type="hidden" value="<?=$key_id?>" name="key_id">
    </tr>
    <tr style="display:none">
        <tr>
            <td colspan='40'>Amount:</td>
            <td><input type="text" name="amount" size="25" value="50000"></td>
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
</table>
</form>
<br><br>
<div style="text-align:center">
<h3>Test Capture/Refund</h3>

<form name ="capture" method="post" action="//<?=$private_url?>/payments/">
<input type="text" id="capture_id" placeholder = "Enter payment id to capture"/>
<input type = "text" id = "amount" name = "amount" placeholder = "Enter amount to capture" value="50000" />
<input type="submit" value="Capture" onClick="javascript:document.capture.action = document.capture.action + document.getElementById('capture_id').value +'/capture'; document.capture.submit(); return false;"/>
</form>

<form name ="refund" method="post" action="//<?=$private_url?>/payments/">
<input type="text" id="refund_id" placeholder="Enter payment id to refund"/>
<input type="text" id="amount" name="amount" value="50000"/>
<input type="submit" value="Refund" onClick="javascript:document.refund.action = document.refund.action + document.getElementById('refund_id').value +'/refund'; document.refund.submit(); return false;"/>
</form>
</div>
<div style="text-align:center">
<h3>Test Create Order</h3>

<form name ="create_order" method="post" action="//<?=$private_url?>/orders">
<table border="1" align="center"  width="300">
<tr>
    <td>
     Recipt
    </td>
    <td>
    <input type = "text" id = "receipt" name = "receipt" placeholder = "Enter Merchant Reference Id" value="3453" />
    </td>
</tr>
<tr>
    <td>
     Currency
    </td>
    <td>
    <input type = "text" id = "currency" name = "currency" placeholder = "Enter currency to create order for" value="INR" />
    </td>
</tr>
<tr>
    <td>
     Amount
    </td>
    <td>
    <input type = "text" id = "amount" name = "amount" placeholder = "Enter amount to create order for" value="50000"/>
    </td>
</tr>
<tr>
    <td>
     Account Number
    </td>
    <td>
    <input type = "text" id = "account_number" name = "account_number" placeholder = "Enter account number to create order for" value="" />
    </td>
</tr>
<tr>
    <td>
     Method
    </td>
    <td>
    <input type = "text" id = "method" name = "method" placeholder = "Enter method" value="netbanking" readonly />
    </td>
</tr>
<tr>
    <td>
     Bank
    </td>
    <td>
    <input type = "text" id = "bank" name = "bank" placeholder = "Enter bank" value="KKBK" readonly/>
    </td>
</tr>
<tr>
    <td>
    <input type="submit" value="Create Order"/>
    </td>
</tr>
</table>
</form>
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
