<?php

/* -----------------------------------------------------------------------------

 Version 2.0


------------------------------------------------------------------------------*/

// *********************
// START OF MAIN PROGRAM
// *********************

// Define Constants
// ----------------
// This is secret for encoding the MD5 hash
// This secret will vary from merchant to merchant
// To not create a secure hash, let SECURE_SECRET be an empty string - ""
$SECURE_SECRET = $_POST['vpc_Secret'];
unset($_POST['vpc_Secret']);

session_start();
$_SESSION['secret'] = $SECURE_SECRET;

// add the start of the vpcURL querystring parameters
$vpcURL = 'https://migs.mastercard.com.au/vpcpay';

$protocol = 'http';

$_POST['vpc_MerchTxnRef'] = time() . rand(10000,99999999);

if(isset($_SERVER['HTTPS'])) {
    if ($_SERVER['HTTPS'] == "on") {
        $protocol = 'https';
    }
}

$_POST['vpc_ReturnURL'] = $protocol.'://'.$_SERVER['HTTP_HOST'] .
                          '/gateway/axis_migs/callback.php';
$_POST['vpc_Currency'] = 'INR';
$_POST['vpc_Locale'] = 'en';
$_POST['vpc_Amount'] = '500';
$_POST['vpc_Currency'] = 'INR';
$_POST['vpc_gateway'] = 'ssl';
$_POST['vpc_CardNum'] = '5123456789012346';
$_POST['vpc_CardExp'] = '1705';
$_POST['vpc_CardSecurityCode'] = '333';
$_POST['vpc_Card'] = 'MasterCard';
$_POST['vpc_Command'] = 'pay';
$_POST['vpc_Version'] = '1';

// The URL link for the receipt to do another transaction.
// Note: This is ONLY used for this example and is not required for
// production code. You would hard code your own URL into your application.

// Get and URL Encode the AgainLink. Add the AgainLink to the array
// Shows how a user field (such as application SessionIDs) could be added
// $_POST['AgainLink']=urlencode($HTTP_REFERER);

// Create the request to the Virtual Payment Client which is a URL encoded GET
// request. Since we are looping through all the data we may as well sort it in
// case we want to create a secure hash and add it to the VPC data if the
// merchant secret has been provided.
$md5HashData = $SECURE_SECRET;
ksort ($_POST);

// set a parameter to show the first pair in the URL
$appendAmp = 0;

foreach($_POST as $key => $value) {

    // create the md5 input and URL leaving out any fields that have no value
    if (strlen($value) > 0) {
        $md5HashData .= $value;
    }
}

$hashedvalue = '';
// Create the secure hash and append it to the Virtual Payment Client Data if
// the merchant secret has been provided.
if (strlen($SECURE_SECRET) > 0) {
    $hashedvalue .= strtoupper(md5($md5HashData));
}
print_r($hashedvalue);

// FINISH TRANSACTION - Redirect the customers using the Digital Order
// ===================================================================
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="no-store, no-cache, must-revalidate">
<META HTTP-EQUIV="PRAGMA" CONTENT="no-store, no-cache, must-revalidate">
<!--
# Currently the body tag was set so that form will be sent automatically.
#
# Merchants are welcome to NOT have this page being sent automatically. They may use this page
# to display a confirmation message, with a submit/confirm button for the cardholders to press.
# Beware that you will need to include the submit button to your hash also.
-->
<body onload="document.order.submit()">
	<form name="order" action="<?php echo $vpcURL?>" method="post">
	<p>Please wait while your payment is being processed...</p>
<?php
	// Add all the fields from the input form, except for the Submit Button and the VPCURL.
	//For Each item In Request.Form
	foreach($_POST as $key => $value) {
		if (strlen($value) > 0) {
?>
        	<input type="text" name="<?php echo $key?>" value="<?php echo $value?>"/><br>
<?php
    	}
	}
?>
	<!-- attach SecureHash -->
    <input type="text" name="vpc_SecureHash" value="<?php echo $hashedvalue?>"/>
	</form>
</body>
</html>
