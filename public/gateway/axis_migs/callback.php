<?php

// *********************
// START OF MAIN PROGRAM
// *********************

// Define Constants
// ----------------
// This is secret for encoding the MD5 hash
// This secret will vary from merchant to merchant
// To not create a secure hash, let SECURE_SECRET be an empty string - ""
session_start();
$SECURE_SECRET = $_SESSION['secret'];


// This method uses the QSI Response code retrieved from the Digital
// Receipt and returns an appropriate description for the QSI Response Code
//
// @param $responseCode String containing the QSI Response Code
//
// @return String containing the appropriate description
//
function getResponseDescription($responseCode) {

    switch ($responseCode) {
        case "0" : $result = "Transaction Successful"; break;
        case "?" : $result = "Transaction status is unknown"; break;
        case "1" : $result = "Unknown Error"; break;
        case "2" : $result = "Bank Declined Transaction"; break;
        case "3" : $result = "No Reply from Bank"; break;
        case "4" : $result = "Expired Card"; break;
        case "5" : $result = "Insufficient funds"; break;
        case "6" : $result = "Error Communicating with Bank"; break;
        case "7" : $result = "Payment Server System Error"; break;
        case "8" : $result = "Transaction Type Not Supported"; break;
        case "9" : $result = "Bank declined transaction (Do not contact Bank)"; break;
        case "A" : $result = "Transaction Aborted"; break;
        case "C" : $result = "Transaction Cancelled"; break;
        case "D" : $result = "Deferred transaction has been received and is awaiting processing"; break;
        case "F" : $result = "3D Secure Authentication failed"; break;
        case "I" : $result = "Card Security Code verification failed"; break;
        case "L" : $result = "Shopping Transaction Locked (Please try the transaction again later)"; break;
        case "N" : $result = "Cardholder is not enrolled in Authentication scheme"; break;
        case "P" : $result = "Transaction has been received by the Payment Adaptor and is being processed"; break;
        case "R" : $result = "Transaction was not processed - Reached limit of retry attempts allowed"; break;
        case "S" : $result = "Duplicate SessionID (OrderInfo)"; break;
        case "T" : $result = "Address Verification Failed"; break;
        case "U" : $result = "Card Security Code Failed"; break;
        case "V" : $result = "Address Verification and Card Security Code Failed"; break;
        default  : $result = "Unable to be determined";
    }
    return $result;
}



//  -----------------------------------------------------------------------------

// This method uses the verRes status code retrieved from the Digital
// Receipt and returns an appropriate description for the QSI Response Code

// @param statusResponse String containing the 3DS Authentication Status Code
// @return String containing the appropriate description

function getStatusDescription($statusResponse) {
    if ($statusResponse == "" || $statusResponse == "No Value Returned") {
        $result = "3DS not supported or there was no 3DS data provided";
    } else {
        switch ($statusResponse) {
            Case "Y"  : $result = "The cardholder was successfully authenticated."; break;
            Case "E"  : $result = "The cardholder is not enrolled."; break;
            Case "N"  : $result = "The cardholder was not verified."; break;
            Case "U"  : $result = "The cardholder's Issuer was unable to authenticate due to some system error at the Issuer."; break;
            Case "F"  : $result = "There was an error in the format of the request from the merchant."; break;
            Case "A"  : $result = "Authentication of your Merchant ID and Password to the ACS Directory Failed."; break;
            Case "D"  : $result = "Error communicating with the Directory Server."; break;
            Case "C"  : $result = "The card type is not supported for authentication."; break;
            Case "S"  : $result = "The signature on the response received from the Issuer could not be validated."; break;
            Case "P"  : $result = "Error parsing input from Issuer."; break;
            Case "I"  : $result = "Internal Payment Server system error."; break;
            default   : $result = "Unable to be determined"; break;
        }
    }
    return $result;
}

//  -----------------------------------------------------------------------------

// If input is null, returns string "No Value Returned", else returns input
function null2unknown($data) {
    if ($data == "") {
        return "No Value Returned";
    } else {
        return $data;
    }
}

//  ----------------------------------------------------------------------------

// If there has been a merchant secret set then sort and loop through all the
// data in the Virtual Payment Client response. While we have the data, we can
// append all the fields that contain values (except the secure hash) so that
// we can create a hash and validate it against the secure hash in the Virtual
// Payment Client response.

// NOTE: If the vpc_TxnResponseCode in not a single character then
// there was a Virtual Payment Client error and we cannot accurately validate
// the incoming data from the secure hash. */

// get and remove the vpc_TxnResponseCode code from the response fields as we
// do not want to include this field in the hash calculation
$vpc_Txn_Secure_Hash = $_GET["vpc_SecureHash"];
unset($_GET["vpc_SecureHash"]);

// set a flag to indicate if hash has been validated
$errorExists = false;

if (strlen($SECURE_SECRET) > 0 && $_GET["vpc_TxnResponseCode"] != "7" && $_GET["vpc_TxnResponseCode"] != "No Value Returned") {

    $md5HashData = $SECURE_SECRET;

    foreach($_GET as $key => $value) {
        if ($key != "vpc_SecureHash" or strlen($value) > 0) {
            $md5HashData .= $value;
        }
    }

    // Validate the Secure Hash (remember MD5 hashes are not case sensitive)
	// This is just one way of displaying the result of checking the hash.
	// In production, you would work out your own way of presenting the result.
	// The hash check is all about detecting if the data has changed in transit.
    if (strtoupper($vpc_Txn_Secure_Hash) == strtoupper(md5($md5HashData))) {
        // Secure Hash validation succeeded, add a data field to be displayed
        // later.
        $hashValidated = "<FONT color='#00AA00'><strong>CORRECT</strong></FONT>";
    } else {
        // Secure Hash validation failed, add a data field to be displayed
        // later.
        $hashValidated = "<FONT color='#FF0066'><strong>INVALID HASH</strong></FONT>";
        $errorExists = true;
    }
} else {
    // Secure Hash was not validated, add a data field to be displayed later.
    $hashValidated = "<FONT color='orange'><strong>Not Calculated - No 'SECURE_SECRET' present.</strong></FONT>";
}

// Define Variables
// ----------------
// Extract the available receipt fields from the VPC Response
// If not present then let the value be equal to 'No Value Returned'

// Standard Receipt Data
$amount          = null2unknown($_GET["vpc_Amount"]);
$locale          = null2unknown($_GET["vpc_Locale"]);
$batchNo         = null2unknown($_GET["vpc_BatchNo"]);
$command         = null2unknown($_GET["vpc_Command"]);
$message         = null2unknown($_GET["vpc_Message"]);
$version         = null2unknown($_GET["vpc_Version"]);
$cardType        = null2unknown($_GET["vpc_Card"]);
$orderInfo       = null2unknown($_GET["vpc_OrderInfo"]);
$receiptNo       = null2unknown($_GET["vpc_ReceiptNo"]);
$merchantID      = null2unknown($_GET["vpc_Merchant"]);
$authorizeID     = null2unknown($_GET["vpc_AuthorizeId"]);
$merchTxnRef     = null2unknown($_GET["vpc_MerchTxnRef"]);
$transactionNo   = null2unknown($_GET["vpc_TransactionNo"]);
$acqResponseCode = null2unknown($_GET["vpc_AcqResponseCode"]);
$txnResponseCode = null2unknown($_GET["vpc_TxnResponseCode"]);


// 3-D Secure Data
$verType         = array_key_exists("vpc_VerType", $_GET)          ? $_GET["vpc_VerType"]          : "No Value Returned";
$verStatus       = array_key_exists("vpc_VerStatus", $_GET)        ? $_GET["vpc_VerStatus"]        : "No Value Returned";
$token           = array_key_exists("vpc_VerToken", $_GET)         ? $_GET["vpc_VerToken"]         : "No Value Returned";
$verSecurLevel   = array_key_exists("vpc_VerSecurityLevel", $_GET) ? $_GET["vpc_VerSecurityLevel"] : "No Value Returned";
$enrolled        = array_key_exists("vpc_3DSenrolled", $_GET)      ? $_GET["vpc_3DSenrolled"]      : "No Value Returned";
$xid             = array_key_exists("vpc_3DSXID", $_GET)           ? $_GET["vpc_3DSXID"]           : "No Value Returned";
$acqECI          = array_key_exists("vpc_3DSECI", $_GET)           ? $_GET["vpc_3DSECI"]           : "No Value Returned";
$authStatus      = array_key_exists("vpc_3DSstatus", $_GET)        ? $_GET["vpc_3DSstatus"]        : "No Value Returned";

// *******************
// END OF MAIN PROGRAM
// *******************

// FINISH TRANSACTION - Process the VPC Response Data
// =====================================================
// For the purposes of demonstration, we simply display the Result fields on a
// web page.

// Show 'Error' in title if an error condition
$errorTxt = "";

// Show this page as an error page if vpc_TxnResponseCode equals '7'
if ($txnResponseCode == "7" || $txnResponseCode == "No Value Returned" || $errorExists) {
    $errorTxt = "Error ";
}

// This is the display title for 'Receipt' page
$title = $_GET["Title"] ?? 'Migs Test Page';

// The URL link for the receipt to do another transaction.
// Note: This is ONLY used for this example and is not required for
// production code. You would hard code your own URL into your application
// to allow customers to try another transaction.
//TK//$againLink = URLDecode($_GET["AgainLink"]);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title><?=$title?> - <?=$errorTxt?>Response Page</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <style type="text/css">
            <!--
            h1       { font-family:Arial,sans-serif; font-size:24pt; color:#08185A; font-weight:100}
            h2.co    { font-family:Arial,sans-serif; font-size:24pt; color:#08185A; margin-top:0.1em; margin-bottom:0.1em; font-weight:100}
            h3.co    { font-family:Arial,sans-serif; font-size:16pt; color:#000000; margin-top:0.1em; margin-bottom:0.1em; font-weight:100}
            body     { font-family:Verdana,Arial,sans-serif; font-size:10pt; color:#08185A background-color:#FFFFFF }
            p        { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#FFFFFF }
            a:link   { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#08185A }
            a:visited{ font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#08185A }
            a:hover  { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#FF0000 }
            a:active { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#FF0000 }
			tr       { height:25px; }
			tr.shade { height:25px; background-color:#E1E1E1 }
			tr.title { height:25px; background-color:#C1C1C1 }
            td       { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#08185A }
            td.red   { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#FF0066 }
            td.green { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#00AA00 }
            th       { font-family:Verdana,Arial,sans-serif; font-size:10pt; color:#08185A; font-weight:bold; background-color:#E1E1E1; padding-top:0.5em; padding-bottom:0.5em}
            input    { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#08185A; background-color:#E1E1E1; font-weight:bold }
            select   { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#08185A; background-color:#E1E1E1; font-weight:bold; width:463 }
            textarea { font-family:Verdana,Arial,sans-serif; font-size:8pt; color:#08185A; background-color:#E1E1E1; font-weight:normal; scrollbar-arrow-color:#08185A; scrollbar-base-color:#E1E1E1 }
            -->
        </style>
    </head>
    <body>
		<!-- start branding table -->
		<table width='100%' border='2' cellpadding='2' bgcolor='#C1C1C1'>
			<tr>
				<td bgcolor='#E1E1E1' width='90%'><h2 class='co'>&nbsp;Payment Client 3.1 Example</h2></td>
				<td bgcolor='#C1C1C1' align='center'><h3 class='co'>MIGS</h3></td>
			</tr>
		</table>
		<!-- end branding table -->
        <!-- End Branding Table -->
        <center><h1><?=$title?> - <?=$errorTxt?>Response Page</h1></center>
        <table width="85%" align="center" cellpadding="5" border="0">
            <tr class="title">
                <td colspan="2" height="25"><P><strong>&nbsp;Basic Transaction Fields</strong></P></td>
            </tr>
            <tr>
                <td align="right" width="55%"><strong><i>VPC API Version: </i></strong></td>
                <td width="45%"><?=$version?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>Command: </i></strong></td>
                <td><?=$command?></td>
            </tr>
            <tr>
                <td align="right"><strong><i>Merchant Transaction Reference: </i></strong></td>
                <td><?=$merchTxnRef?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>Merchant ID: </i></strong></td>
                <td><?=$merchantID?></td>
            </tr>
            <tr>
                <td align="right"><strong><i>Order Information: </i></strong></td>
                <td><?=$orderInfo?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>Purchase Amount: </i></strong></td>
                <td><?=$amount?></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <font color="#C1C1C1">Fields above are the request values returned.<br />
                    <HR />
                    Fields below are the response fields for a Standard Transaction.<br /></font>
                </td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>VPC Transaction Response Code: </i></strong></td>
                <td><?=$txnResponseCode?></td>
            </tr>
            <tr>
                <td align="right"><strong><i>Transaction Response Code Description: </i></strong></td>
                <td><?=getResponseDescription($txnResponseCode)?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>Message: </i></strong></td>
                <td><?=$message?></td>
            </tr>
<?
    // only display the following fields if not an error condition
    if ($txnResponseCode != "7" && $txnResponseCode != "No Value Returned") {
?>
            <tr>
                <td align="right"><strong><i>Receipt Number: </i></strong></td>
                <td><?=$receiptNo?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>Transaction Number: </i></strong></td>
                <td><?=$transactionNo?></td>
            </tr>
            <tr>
                <td align="right"><strong><i>Acquirer Response Code: </i></strong></td>
                <td><?=$acqResponseCode?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>Bank Authorization ID: </i></strong></td>
                <td><?=$authorizeID?></td>
            </tr>
            <tr>
                <td align="right"><strong><i>Batch Number: </i></strong></td>
                <td><?=$batchNo?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>Card Type: </i></strong></td>
                <td><?=$cardType?></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <font color="#C1C1C1">Fields above are for a Standard Transaction<br />
                    <HR />
                    Fields below are additional fields for extra functionality.</font><br />
                </td>
            </tr>
            <tr>
                <td colspan="2"><HR /></td>
            </tr>
            <tr class="title">
                <td colspan="2" height="25"><P><strong>&nbsp;3-D Secure Fields</strong></P></td>
            </tr>
            <tr>
                <td align="right"><strong><i>Unique 3DS transaction identifier: </i></strong></td>
                <td class="red"><?=$xid?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>3DS Authentication Verification Value: </i></strong></td>
                <td class="red"><?=$token?></td>
            </tr>
            <tr>
                <td align="right"><strong><i>3DS Electronic Commerce Indicator: </i></strong></td>
                <td class="red"><?=$acqECI?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>3DS Authentication Scheme: </i></strong></td>
                <td class="red"><?=$verType?></td>
            </tr>
            <tr>
                <td align="right"><strong><i>3DS Security level used in the AUTH message: </i></strong></td>
                <td class="red"><?=$verSecurLevel?></td>
            </tr>
            <tr class="shade">
                <td align="right">
                    <strong><i>3DS CardHolder Enrolled: </strong>
                    <br />
                    <font size="1">Takes values: <strong>Y</strong> - Yes <strong>N</strong> - No</i></font>
                </td>
                <td class="red"><?=$enrolled?></td>
            </tr>
            <tr>
                <td align="right">
                    <i><strong>Authenticated Successfully: </strong><br />
                    <font size="1">Only returned if CardHolder Enrolled = <strong>Y</strong>. Takes values:<br />
                    <strong>Y</strong> - Yes <strong>N</strong> - No <strong>A</strong> - Attempted to Check <strong>U</strong> - Unavailable for Checking</font></i>
                </td>
                <td class="red"><?=$authStatus?></td>
            </tr>
            <tr class="shade">
                <td align="right"><strong><i>Payment Server 3DS Authentication Status Code: </i></strong></td>
                <td class="green"><?=$verStatus?></td>
            </tr>
            <tr>
                <td align="right"><i><strong>3DS Authentication Status Code Description: </strong></i></td>
                <td class="green"><?=getStatusDescription($verStatus)?></td>
            </tr>
            <tr>
                <td colspan="2" class="red" align="center">
                    <br />The 3-D Secure values shown in red are those values that are important values to store in case of future transaction repudiation.
                </td>
            </tr>
            <tr>
                <td colspan="2" class="green" align="center">
                    The 3-D Secure values shown in green are for information only and are not required to be stored.
                </td>
            </tr>
            <tr>
                <td colspan="2"><HR /></td>
            </tr>
            <tr class="title">
                <td colspan="2" height="25"><P><strong>&nbsp;Hash Validation</strong></P></td>
            </tr>
            <tr>
                <td align="right"><strong><i>Hash Validated Correctly: </i></strong></td>
                <td><?=$hashValidated?></td>
            </tr>
<?
} ?>    </table>
        <!-- TK <center><P><a href='<?=$againLink?>'>New Transaction</a></P></center> -->
    </body>
</html>

