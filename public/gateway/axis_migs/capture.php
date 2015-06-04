<?php

/* -----------------------------------------------------------------------------

 Version 2.0

------------------ Disclaimer --------------------------------------------------

Copyright 2004 Dialect Holdings.  All rights reserved.

This document is provided by Dialect Holdings on the basis that you will treat
it as confidential.

No part of this document may be reproduced or copied in any form by any means
without the written permission of Dialect Holdings.  Unless otherwise expressly
agreed in writing, the information contained in this document is subject to
change without notice and Dialect Holdings assumes no responsibility for any
alteration to, or any error or other deficiency, in this document.

All intellectual property rights in the Document and in all extracts and things
derived from any part of the Document are owned by Dialect and will be assigned
to Dialect on their creation. You will protect all the intellectual property
rights relating to the Document in a manner that is equal to the protection
you provide your own intellectual property.  You will notify Dialect
immediately, and in writing where you become aware of a breach of Dialect's
intellectual property rights in relation to the Document.

The names "Dialect", "QSI Payments" and all similar words are trademarks of
Dialect Holdings and you must not use that name or any similar name.

Dialect may at its sole discretion terminate the rights granted in this
document with immediate effect by notifying you in writing and you will
thereupon return (or destroy and certify that destruction to Dialect) all
copies and extracts of the Document in its possession or control.

Dialect does not warrant the accuracy or completeness of the Document or its
content or its usefulness to you or your merchant customers.   To the extent
permitted by law, all conditions and warranties implied by law (whether as to
fitness for any particular purpose or otherwise) are excluded.  Where the
exclusion is not effective, Dialect limits its liability to $100 or the
resupply of the Document (at Dialect's option).

Data used in examples and sample data files are intended to be fictional and
any resemblance to real persons or companies is entirely coincidental.

Dialect does not indemnify you or any third party in relation to the content or
any use of the content as contemplated in these terms and conditions.

Mention of any product not owned by Dialect does not constitute an endorsement
of that product.

This document is governed by the laws of New South Wales, Australia and is
intended to be legally binding.

-------------------------------------------------------------------------------

This example assumes that a form has been sent to this example with the
required fields. The example then processes the command and displays the
receipt or error to a HTML page in the users web browser.

NOTE:
=====
  You may have installed the libeay32.dll and ssleay32.dll libraries
  into your x:\WINNT\system32 directory to run this example.

--------------------------------------------------------------------------------

 @author Dialect Payment Solutions Pty Ltd Group

------------------------------------------------------------------------------*/

// *********************
// START OF MAIN PROGRAM
// *********************

// add the start of the vpcURL querystring parameters
$vpcURL = $_POST["virtualPaymentClientURL"];

// This is the title for display
$title  = $_POST["Title"];

// Remove the Virtual Payment Client URL from the parameter hash as we
// do not want to send these fields to the Virtual Payment Client.
unset($_POST["virtualPaymentClientURL"]);
unset($_POST["SubButL"]);
unset($_POST["Title"]);

// create a variable to hold the POST data information and capture it
$postData = "";

$ampersand = "";
foreach($_POST as $key => $value) {
    // create the POST data input leaving out any fields that have no value
    if (strlen($value) > 0) {
        $postData .= $ampersand . urlencode($key) . '=' . urlencode($value);
        $ampersand = "&";
    }
}

// Get a HTTPS connection to VPC Gateway and do transaction
// turn on output buffering to stop response going to browser
ob_start();

// initialise Client URL object
$ch = curl_init();

// set the URL of the VPC
curl_setopt ($ch, CURLOPT_URL, $vpcURL);
curl_setopt ($ch, CURLOPT_POST, 1);
curl_setopt ($ch, CURLOPT_POSTFIELDS, $postData);

// (optional) set the proxy IP address and port
//curl_setopt ($ch, CURLOPT_PROXY, "192.168.21.13:80");

// (optional) certificate validation
// trusted certificate file
//curl_setopt($ch, CURLOPT_CAINFO, "c:/temp/ca-bundle.crt");

//turn on/off cert validation
// 0 = don't verify peer, 1 = do verify
curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);

// 0 = don't verify hostname, 1 = check for existence of hostame, 2 = verify
//curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);

// connect
curl_exec ($ch);

// get response
$response = ob_get_contents();

// turn output buffering off.
ob_end_clean();

// set up message paramter for error outputs
$message = "";

// serach if $response contains html error code
if(strchr($response,"<html>") || strchr($response,"<html>")) {;
    $message = $response;
} else {
    // check for errors from curl
    if (curl_error($ch))
          $message = "%s: s". curl_errno($ch) . "<br/>" . curl_error($ch);
}

// close client URL
curl_close ($ch);

// Extract the available receipt fields from the VPC Response
// If not present then let the value be equal to 'No Value Returned'
$map = array();

// process response if no errors
if (strlen($message) == 0) {
    $pairArray = split("&", $response);
    foreach ($pairArray as $pair) {
        $param = split("=", $pair);
        $map[urldecode($param[0])] = urldecode($param[1]);
    }
    $message         = null2unknown($map, "vpc_Message");
}

// Standard Receipt Data
# merchTxnRef not always returned in response if no receipt so get input
//TK//$merchTxnRef     = $vpc_MerchTxnRef;
$merchTxnRef     = $_POST["vpc_MerchTxnRef"];


$amount          = null2unknown($map, "vpc_Amount");
$locale          = null2unknown($map, "vpc_Locale");
$batchNo         = null2unknown($map, "vpc_BatchNo");
$command         = null2unknown($map, "vpc_Command");
$version         = null2unknown($map, "vpc_Version");
$cardType        = null2unknown($map, "vpc_Card");
$orderInfo       = null2unknown($map, "vpc_OrderInfo");
$receiptNo       = null2unknown($map, "vpc_ReceiptNo");
$merchantID      = null2unknown($map, "vpc_Merchant");
$authorizeID     = null2unknown($map, "vpc_AuthorizeId");
$transactionNo   = null2unknown($map, "vpc_TransactionNo");
$acqResponseCode = null2unknown($map, "vpc_AcqResponseCode");
$txnResponseCode = null2unknown($map, "vpc_TxnResponseCode");

// AMA Transaction Data
$shopTransNo     = null2unknown($map, "vpc_ShopTransactionNo");
$authorisedAmount= null2unknown($map, "vpc_AuthorisedAmount");
$capturedAmount  = null2unknown($map, "vpc_CapturedAmount");
$refundedAmount  = null2unknown($map, "vpc_RefundedAmount");
$ticketNumber    = null2unknown($map, "vpc_TicketNo");


/*********************
* END OF MAIN PROGRAM
*********************/

// FINISH TRANSACTION - Process the VPC Response Data
// =====================================================
// For the purposes of demonstration, we simply display the Result fields on a
// web page.

// Show 'Error' in title if an error condition
$errorTxt = "";
// Show the display page as an error page
if ($txnResponseCode == "7" || $txnResponseCode == "No Value Returned") {
    $errorTxt = "Error ";
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title><?=$title?> - <?=$errorTxt?>Response Page</title>
        <meta http-equiv="Content-Type" content="text/html, charset=iso-8859-1">
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
        <!-- Start Branding Table -->
        <table width="100%" border="2" cellpadding="2" class="title">
            <tr>
                <td class="shade" width="90%"><h2 class="co">&nbsp;Virtual Payment Client Example</h2></td>
                <td class="title" align="center"><h3 class="co">MIGS</h3></td>
            </tr>
        </table>
        <!-- End Branding Table -->
        <center><h1><?=$title?> - <?=$errorTxt?>Response Page</h1></center>
        <table width="80%" align="center" cellpadding="5" border="0">
          <tr class="title">
            <td colspan="2" height="25"><p><strong>&nbsp;Capture Transaction Fields</strong></p></td>
          </tr>
          <tr>
            <td align="right" width="50%"><strong><i>VPC API Version: </i></strong></td>
            <td width="50%"><?=$version?></td>
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
            <td align="right"><strong><i>Capture Amount: </i></strong></td>
            <td><?=$amount?></td>
          </tr>
          <tr>
            <td colspan = '2' align='center'><font color='#C1C1C1'>Fields above are the request values returned.</font><br/><hr/>
            </td>
          </tr>

          <tr class="shade">
            <td align='right'><b><i>VPC Transaction Response Code: </i></b></td>
            <td><?=$txnResponseCode?></td>
          </tr>
          <tr>
            <td align='right'><b><i>Transaction Response Code Description: </i></b></td>
            <td><?=getResponseDescription($txnResponseCode)?></td>
          </tr>
          <tr class="shade">
            <td align='right'><b><i>Message: </i></b></td>
            <td><?=$message?></td>
          </tr>
<?
    // only display the following fields if not an error condition
    if ($txnResponseCode != "7" && $txnResponseCode != "No Value Returned") {
?>
          <tr>
            <td align='right'><b><i>Receipt Number: </i></b></td>
            <td><?=$receiptNo?></td>
          </tr>
          <tr class="shade">
            <td align='right'><b><i>Transaction Number: </i></b></td>
            <td><?=$transactionNo?></td>
          </tr>
          <tr>
            <td align='right'><b><i>Acquirer Response Code: </i></b></td>
            <td><?=$acqResponseCode?></td>
          </tr>
          <tr class="shade">
            <td align='right'><b><i>Bank Authorization ID: </i></b></td>
            <td><?=$authorizeID?></td>
          </tr>
          <tr>
            <td align='right'><b><i>Batch Number: </i></b></td>
            <td><?=$batchNo?></td>
          </tr>
          <tr class="shade">
            <td align='right'><b><i>Card Type: </i></b></td>
            <td><?=$cardType?></td>
          </tr>

          <tr>
            <td colspan='2' align='center'><font color='#C1C1C1'>Fields above are for a standard transaction<br/><hr/>
                Fields below are additional fields for extra functionality.</font><br/></td>
          </tr>

          <tr class="title">
            <td colspan="2" height="25"><p><b>&nbsp;Financial Transaction Fields</b></p></td>
          </tr>
          <tr>
            <td align='right'><b><i>Shopping Transaction Number: </i></b></td>
            <td><?=$shopTransNo?></td>
          </tr>
          <tr class="shade">
            <td align='right'><b><i>Authorised Amount: </i></b></td>
            <td><?=$authorisedAmount?></td>
          </tr>
          <tr>
            <td align='right'><b><i>Captured Amount: </i></b></td>
            <td><?=$capturedAmount?></td>
          </tr>
          <tr class="shade">
            <td align='right'><b><i>Captureed Amount: </i></b></td>
            <td><?=$refundedAmount?></td>
          </tr>
          <tr>
            <td align='right'><b><i>Ticket Number: </i></b></td>
            <td><?=$ticketNumber?></td>
          </tr>
<? } ?>
        </table>

    <center><p><A HREF='<?=$HTTP_REFERER?>'>New Transaction</a></p></center>

    </body>
    </html>

<?
// End Processing

//  ----------------------------------------------------------------------------

// This function uses the Transaction Response code retrieved from the Digital
// Receipt and returns an appropriate description for the vpc_TxnResponseCode

// @param $responseCode String containing the vpc_TxnResponseCode

// @return String containing the appropriate description

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

//  ----------------------------------------------------------------------------

// This subroutine takes a data String and returns a predefined value if empty
// If data Sting is null, returns string "No Value Returned", else returns input

// @param $in String containing the data String

// @return String containing the output String

function null2unknown($map, $key) {
    if (array_key_exists($key, $map)) {
        if (!is_null($map[$key])) {
            return $map[$key];
        }
    }
    return "No Value Returned";
}

//  ----------------------------------------------------------------------------
