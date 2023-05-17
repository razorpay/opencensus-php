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
        <td colspan="40">Select Method: </td>
        <td>
            <select name="method">
                <option value="netbanking">Net Banking</option>
                <option value="app">App</option>
                <option value="card" selected>Card</option>
                <option value="wallet">Wallet</option>
                <option value="emi">Emi</option>
                <option value="upi">UPI</option>
                <option value="emandate">E-Mandate</option>
                <option value="cardless_emi">Cardless Emi</option>
                <option value="paylater">PayLater</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Bank (Netbanking): </td>
        <td>
            <select name="bank">
                <option value="CSBK">Catholic Syrian Bank</option>
                <option value="BARB_C">Bank of Baroda - Corporate</option>
                <option value="BARB_R">Bank of Baroda - Retail</option>
                <option value="HDFC">HDFC Bank</option>
                <option value="HDFC_C">HDFC Bank - Corporate</option>
                <option value="CORP">Corporation Bank</option>
                <option value="ALLA">Allahabad Bank</option>
                <option value="SBIN">SBI Bank</option>
                <option value="ICIC">ICICI Bank</option>
                <option value="AIRP">Airtel Payments Bank</option>
                <option value="ORBC">Obc Bank</option>
                <option value="FDRL">Federal Bank</option>
                <option value="IDFB">IDFC Bank</option>
                <option value="RATN">RBL Bank</option>
                <option value="RATN_C">RBL Bank - Corporate</option>
                <option value="CITI">CITI Bank</option>
                <option value="UTIB">Axis Bank</option>
                <option value="YESB">Yes Bank</option>
                <option value="KKBK">Kotak Bank</option>
                <option value="VIJB">Vijaya Bank</option>
                <option value="SBTR">State Bank of Travancore</option>
                <option value="SBBJ">State Bank of Bikaner and Jaipur</option>
                <option value="JAKA">J&K Bank</option>
                <option value="UBIN">United Bank</option>
                <option value="BARB">Bank of Baroda</option>
                <option value="INDB">Indusind Bank</option>
                <option value="PUNB_R">Punjab National Bank - Retail</option>
                <option value="PUNB_C">Punjab National Bank - Corporate</option>
                <option value="ESFB">Equitas Small Finance Bank</option>
                <option value="CBIN">CBI</option>
                <option value="CNRB">Canara Bank</option>
                <option value="VIJB">Vijaya Bank</option>
                <option value="UBIN">Union Bank</option>
                <option value="SCBL">Standard Chartered Bank</option>
                <option value="CIUB">City union Bank</option>
                <option value="IDIB">Indian Bank</option>
                <option value="IBKL">Industrial Development Bank of India</option>
                <option value="YESB">Yes Bank</option>
                <option value="SIBL">South Indian Bank</option>
                <option value="KVBL">Karur Vysya Bank</option>
                <option value="SVCB">SVC Co-Operative Bank Ltd.</option>
                <option value="JSFB">Jana Small Finance Bank</option>
                <option value="IOBA">Indian Overseas Bank</option>
                <option value="FSFB">Fincare Small Finance Bank</option>
                <option value="DCBL">Development Credit Bank</option>
                <option value="BKDN">Dena bank</option>
                <option value="KKBK_C">Kotak Mahindra Bank - Corporate</option>
                <option value="DCBL">Development Credit Bank</option>
                <option value="AUBL">AU Small Finance</option>
                <option value="DLXB">DhanlaxmiBank</option>
                <option value="NSPB">NSDL Payments Bank Limited</option>
                <option value="BDBL">Bandhan Bank</option>
                <option value="SRCB">Saraswat Bank</option>
                <option value="UCBA">United Commercial Bank</option>
                <option value="TMBL">Tamilnad Mercantile Bank</option>
                <option value="KARB">Karnataka Bank</option>
                <option value="UJVN">Ujjivan Small Finance Bank</option>
                <option value="SRCB">Saraswat Bank</option>
                <option value="DBSS">DBS Bank</option>
                <option value="AUBL_C">AU Small Finance Bank - Corporate</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Wallet </td>
        <td>
            <select name="wallet">
                <option value="amazonpay" selected>Amazon Pay</option>
                <option value="paytm">Paytm</option>
                <option value="paypal">Paypal</option>
                <option value="mobikwik">Mobikwik</option>
                <option value="payzapp">Payzapp</option>
                <option value="payumoney">Payumoney</option>
                <option value="olamoney">Olamoney</option>
                <option value="airtelmoney">Airtelmoney</option>
                <option value="freecharge">Freecharge</option>
                <option value="bajaj">Bajaj Pay</option>
                <option value="jiomoney">JioMoney</option>
                <option value="sbibuddy">SBI Buddy</option>
                <option value="openwallet">Openwallet (B2B)</option>
                <option value="mpesa">Vodafone Mpesa</option>
            </select>
        </td>
    </tr>
<!--    "uncomment below for device parameters testing"-->
    <tr>
        <td colspan="40">Device Parameters </td>
        <td>
            <select name="device[is_roming]">
                <option value="false" selected>False</option>
            </select>
        </td>
        <td>
            <select name="device[carrier_network]">
                <option value="Idea" selected>Idea</option>
            </select>
        </td>
        <td>
            <select name="device[carrier_Id]">
                <option value="hashed_String" selected>hashed_String</option>
            </select>
        </td>
        <td>
            <select name="device[device_Id]">
                <option value="hashed_String" selected>hashed_String</option>
            </select>
        </td>
        <td>
            <select name="device[device_manufacturer]">
                <option value="OnePlus" selected>OnePlus</option>
            </select>
        </td>
        <td>
            <select name="device[device_model]">
                <option value="ONEPLUS_A5000" selected>ONEPLUS_A5000</option>
            </select>
        </td>
        <td>
            <select name="device[serial_number]">
                <option value="hashed_String" selected>hashed_String</option>
            </select>
        </td>
        <td>
            <select name="device[ip_address]">
                <option value="192.168.14.30" selected>192.168.14.30</option>
            </select>
        </td>
        <td>
            <select name="device[wifi_ssid]">
                <option value="hashed_String" selected>hashed_String</option>
            </select>
        </td>
        <td>
            <select name="device[android_id]">
                <option value="hashed_String" selected>hashed_String</option>
            </select>
        </td>
        <td>
            <select name="device[installed_apps]">
                <option value="s,v,g,t" selected>hashed_String</option>
            </select>
        </td>
        <td>
            <select name="device[advertising_id]">
                <option value="250edd01-878f-4f49-b4ba-71b2681440a0" selected>250edd01-878f-4f49-b4ba-71b2681440a0</option>
            </select>
        </td>
        <td>
            <select name="device[safety_net_cts_profile_match]">
                <option value="true" selected>True</option>
            </select>
        </td>
        <td>
            <select name="device[safety_net_basic_integrity]">
                <option value="true" selected>True</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select Provider </td>
        <td>
            <select name="provider">
                <option value="zestmoney">Zestmoney</option>
                <option value="earlysalary">Earlysalary</option>
                <option value="flexmoney">Flexmoney</option>
                <option value="epaylater">EPayLater</option>
                <option value="getsimpl">Simpl</option>
                <option value="icic">Icici Paylater</option>
                <option value="hdfc">HDFC Bank Paylater</option>
                <option value="kkbk">Kotak Bank</option>
                <option value="idfb">IDFC First Bank</option>
                <option value="hcin">Home Credit</option>
                <option value="fdrl">Federal Bank</option>
                <option value="twid">Twid</option>
                <option value="bajajfinserv">Bajaj Finserv</option>
                <option value="walnut369">Walnut369</option>
                <option value="lazypay">Lazypay</option>
                <option value="barb">Bank of Baroda</option>
                <option value="krbe">KreditBee</option>
                <option value="cshe">CASHe</option>
                <option value="tvsc">TVS Credit</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Select EMI Duration</td>
        <td>
            <select name="emi_duration">
                <option value="">Select EMI duration</option>
                <option value="3" selected>3 Months @12%</option>
                <option value="6">6 Months @12%</option>
                <option value="9">9 Months @14%</option>
                <option value="12">12 Months @14%</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="40">Razorpay Key:</td>
        <td>
            <input type="text" value="<?=$key_id?>" name="key_id">
        </td>
    </tr>
    <tr>
        <td colspan="40">Razorpay Partner Token:</td>
        <td>
            <input type="text" value="" name="partner_token">
        </td>
    </tr>
    <tr>
        <td colspan='40'>Card Holder Name:</td>
        <td><input type="text" name="card[name]" size="25" value="shashank" autocomplete="cc-name"></td>
        <!-- <td><input type="text" name="callback_url" value="<?= $callback_url ?>"></td> -->
        <td><input type="hidden" value="INR" name="currency"></td>
    </tr>
    <tr>
        <td colspan="40"><b>Card No: </b> </td>
        <td><input type="text" name="card[number]" value="5577810000000004" size="25" autocomplete="cc-number"></td>
    </tr>
    <tr>
        <td colspan="40">CVV:</td>
        <td><input size="3" type="text" name="card[cvv]" value="123" maxlength=4 autocomplete="cc-csc"></td>
    </tr>
    <tr>
        <td colspan ='40'>Exp Date:</td>
        <td><input type="text" name="card[expiry_month]" value="12" autocomplete="cc-exp-month"></td>
        <td><input type="text" name="card[expiry_year]" value="2024" autocomplete="cc-exp-year"></td>
        <tr>
            <td colspan='40'>Amount:</td>
            <td><input type="text" name="amount" size="25" value="350000"></td>
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
        <td><input type="text" name="email" size="25" value="test@razorpay.com" autocomplete="email"></td>
        <td><input type="text" name="contact" size="25" value="9876543210" autocomplete="tel"></td>
    </tr>
    <tr>
        <td colspan='40'>Razorpay Order Id:</td>
        <td><input type="text" name="order_id" size="25" value=""></td>
    </tr>
    <tr>
        <td colspan='40'>Account Id:</td>
        <td><input type="text" name="account_id" size="25" value=""></td>
    </tr>
    <tr>
        <td colspan='40'>Order Id:</td>
        <td><input type="text" name="notes[order_id]" size="25" value="3453"></td>
    </tr>
    <tr>
        <td colspan='40'>Customer Id:</td>
        <td><input type="text" name="customer_id" size="25" value=""></td>
    </tr>
    <tr>
        <td colspan='40'>App Token:</td>
        <td><input type="text" name="app_token" size="25" value=""></td>
    </tr>
    <tr>
        <td colspan='40'>VPA:</td>
        <td><input type="text" name="vpa" size="25" value="nemomobile@imobile" autocomplete="vpa"></td>
    </tr>
    <tr>
        <td colspan='40'>Bank Account:</td>
        <td><input type="text" name="bank_account[account_number]" size="25" value="" placeholder="Account Number"></td>
        <td><input type="text" name="bank_account[ifsc]" size="25" value="" placeholder="IFSC"></td>
        <td><input type="text" name="bank_account[name]" size="25" value="" placeholder="Name"></td>
        <td>
            <select name="bank_account[account_type]">
                <option value="">Please Select</option>
                <option value="savings">Savings</option>
                <option value="current">Current</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan='40'>Token recurring:</td>
        <td><input type="text" name="recurring_token[max_amount]" size="25" value=""></td>
        <td><input type="text" name="recurring_token[expire_by]" size="25" value=""></td>
    </tr>
    <tr>
        <td colspan="40">Select Auth Type </td>
        <td>
            <select name="auth_type">
                <option value="">Please Select</option>
                <option value="pin">Card - PIN</option>
                <option value="skip">Card - SKIP</option>
                <option value="otp">Card - OTP</option>
                <option value="debitcard">eMandate - Debit Card</option>
                <option value="netbanking">eMandate - Netbanking</option>
                <option value="aadhaar">eMandate - Aadhaar</option>
                <option value="aadhaar_fp">eMandate - Aadhaar finger print</option>
            </select>
        </td>
    </tr>
<!--    <tr>-->
<!--        <td colspan='40'>Aadhaar Number / Aadhaar VID:</td>-->
<!--        <td><input type="text" name="aadhaar[number]" size="12" value="123456789012"></td>-->
<!--        <td><input type="text" name="aadhaar[vid]" size="16" value="1234567890123456"></td>-->
<!--    </tr>-->
    <tr>
        <td colspan="40">Select Preferred Auth </td>
        <td>
            <input type="checkbox" name="preferred_auth[]" value="pin">Card - PIN<br>
            <input type="checkbox" name="preferred_auth[]" value="otp">Card - OTP<br>
        </td>
    </tr>
    <tr>
        <td colspan='40'>Token:</td>
        <td><input type="text" name="token" size="25" value=""></td>
        <td><input type="checkbox" name="save" value="1" id="save"><label for="save">save</label></td>
    </tr>
    <tr>
        <td colspan='40'><label for="recurring">Recurring:</label></td>
        <td><input type="checkbox" name="recurring" value="1" id="recurring"></td>
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
    <option value="EUR">Euro</option>
    <option value="SGD">Singapore Dollar</option>
</select>
    <input type="submit" value="Capture" onClick="javascript:document.capture.action = document.capture.action + document.getElementById('capture_id').value +'/capture'; document.capture.submit(); return false;"/>
</form>

<form name ="refund" method="post" action="//<?=$private_url?>/payments/">
<input type="text" id="refund_id" placeholder="Enter payment id to refund"/>
<input type="text" id="amount" name="amount" value="100"/>
<select name="speed">
    <option value="normal">Normal</option>
    <option value="optimum">Optimum</option>
</select>
<input type="submit" value="Refund" onClick="javascript:document.refund.action = document.refund.action + document.getElementById('refund_id').value +'/refund'; document.refund.submit(); return false;"/>
</form>
</div>
</div>
</body>
</html>
