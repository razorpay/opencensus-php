<?php
// *********************
// START OF MAIN PROGRAM
// *********************

// add the start of the vpcURL querystring parameters
$vpcURL = $_POST["vpc_URL"];

// This is the title for display
$title  = $_POST["Title"];

$SECURE_SECRET = ""; /*Add secure secret here*/

ksort ($_POST);
// Remove the URL from the parameter hash as we 
// do not want to send these fields to the ePP.
unset($_POST["vpc_URL"]); 
unset($_POST["SubButL"]);
unset($_POST["Title"]);

$SHA256HashData = $SECURE_SECRET;
ksort ($_POST);

// create a variable to hold the POST data information and capture it
$postData = "";

$ampersand = "";
foreach($_POST as $key => $value) {
    // create the POST data input leaving out any fields that have no value
    if (strlen($value) > 0) {
        $postData .= $ampersand . urlencode($key) . '=' . urlencode($value);
        $ampersand = "&";
	
	$SHA256HashData .= $value;
    }
}

if (strlen($SECURE_SECRET) > 0) {
    $postData .= "&SecureHash=" . strtoupper(hash("sha256",$SHA256HashData,false));
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

/*Create secure hash for response*/
$vpc_Txn_Secure_Hash = null2unknown($map, "SecureHash");
$vpc_TxnResponseCode = null2unknown($map, "vpc_TxnResponseCode");
$SHA256HashData = "";

if ($vpc_Txn_Secure_Hash != "No Value Returned" ){

	if (strlen($SECURE_SECRET) > 0 && vpc_TxnResponseCode != "7") {

	    $SHA256HashData = $SECURE_SECRET;

	    ksort ($map);
	    // sort all the incoming vpc response fields and leave out any with no value
	    foreach($map as $key => $value) {
		if ($key != "SecureHash") {
		    $SHA256HashData .= $value;
		}
	    }
	}

	$PHP_HASH = strtoupper(hash("sha256",$SHA256HashData,false));


	if (strtoupper($vpc_Txn_Secure_Hash) == strtoupper(hash("sha256",$SHA256HashData,false))) {
		// Secure Hash validation succeeded, add a data field to be displayed
		// later.
		$hashValidated = "<FONT color='#00AA00'><strong>CORRECT</strong></FONT>";
	    } else {
		// Secure Hash validation failed, add a data field to be displayed
		// later.
		$hashValidated = "<FONT color='#FF0066'><strong>INVALID HASH</strong></FONT>";
		$errorExists = true;
	    }
}
else
{
$hashValidated = "<FONT color='#FF0066'><strong>Hash not present</strong></FONT>";
}


/*Create secure hash for response End*/


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

// 3-D Secure Data
$verType         = null2unknown($map, "vpc_VerType");
$verStatus       = null2unknown($map, "vpc_VerStatus");
$token             = null2unknown($map, "vpc_VerToken");
$verSecurLevel   = null2unknown($map, "vpc_VerSecurityLevel");
$enrolled         = null2unknown($map, "vpc_3DSenrolled");
$xid             = null2unknown($map, "vpc_3DSXID");
$acqECI             = null2unknown($map, "vpc_3DSECI");
$authStatus         = null2unknown($map, "vpc_3DSstatus");

// AMA Transaction Data
$shopTransNo     = null2unknown($map, "vpc_ShopTransactionNo");
$authorisedAmount= null2unknown($map, "vpc_AuthorisedAmount");
$capturedAmount  = null2unknown($map, "vpc_CapturedAmount");
$refundedAmount  = null2unknown($map, "vpc_RefundedAmount");
$ticketNumber    = null2unknown($map, "vpc_TicketNo");


// Define an AMA transaction output for Refund & Capture transactions
$amaTransaction = true;
if ($shopTransNo == "No Value Returned") {
    $amaTransaction = false;
}

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
        <title><?php echo $title ?> - <?php echo $errorTxt ?>Response Page</title>
        <meta http-equiv="Content-Type" content="text/html, charset=iso-8859-1">
    <style type='text/css'>
        <!--
        h1       { font-family:Arial,sans-serif; font-size:20pt; color:#08185A; font-weight:600; margin-bottom:0.1em}
        h2       { font-family:Arial,sans-serif; font-size:14pt; color:#08185A; font-weight:100; margin-top:0.1em}
        h2.co    { font-family:Arial,sans-serif; font-size:24pt; color:#08185A; margin-top:0.1em; margin-bottom:0.1em; font-weight:100}
        h3       { font-family:Arial,sans-serif; font-size:16pt; color:#08185A; margin-top:0.1em; margin-bottom:0.1em; font-weight:100}
        h3.co    { font-family:Arial,sans-serif; font-size:16pt; color:#FFFFFF; margin-top:0.1em; margin-bottom:0.1em; font-weight:100}
        BODY     { font-family:Verdana,Arial,sans-serif; font-size:10pt; color:#08185A background-color:#FFFFFF }
        th       { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#08185A; font-weight:bold; background-color:#E1E1E1; padding-top:0.5em; padding-bottom:0.5em}
        TR       { height:25px; }
        TR.shade { height:25px; background-color:#E1E1E1 }
        TR.title { height:25px; background-color:#C1C1C1 }
        td       { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#08185A }
        p        { font-family:Verdana,Arial,sans-serif; font-size:10pt; color:#FFFFFF }
        p.blue   { font-family:Verdana,Arial,sans-serif; font-size:7pt;  color:#08185A }
        P.red    { font-family:Verdana,Arial,sans-serif; font-size:7pt;  color:#FF0066 }
        P.green  { font-family:Verdana,Arial,sans-serif; font-size:7pt;  color:#00AA00 }
        DIV.bl   { font-family:Verdana,Arial,sans-serif; font-size:7pt;  color:#C1C1C1 }
        DIV.red  { font-family:Verdana,Arial,sans-serif; font-size:7pt;  color:#FF0066 }
        LI       { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#FF0066 }
        INPUT    { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#08185A; background-color:#E1E1E1; font-weight:bold }
        SELECT   { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#08185A; background-color:#E1E1E1; font-weight:bold; width:420 }
        TEXTAREA { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#08185A; background-color:#E1E1E1; font-weight:normal; scrollbar-arrow-color:#08185A; scrollbar-base-color:#E1E1E1 }
        a:link   { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#08185A }
        a:visited{ font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#08185A }
        a:hover  { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#FF0000 }
        a:active { font-family:Verdana,Arial,sans-serif; font-size:8pt;  color:#FF0000 }
        -->
    </style>
    </head>
    <body>

<table width='100%' border='2' cellpadding='2' bgcolor='#C1C1C1'>
    <tr>
        <td bgcolor='#E1E1E1' width='90%'>
            <H2 class='co'>&nbsp;ePP Client QueryDR Example</H2>
        </td>
        <td bgcolor='#C1C1C1' align='center'>
            <h3 class='co'>ePP</h3>
        </td>
    </tr>
</table>
<!-- End branding table -->
        <center><h1><?php echo $title?> - <?php echo $errorTxt?>Response Page</h1></center>
    <table width="85%" align='center' cellpadding='5' border='0'>
      
    <tr class="title">
        <td colspan="2" height="25"><P><strong>&nbsp;QueryDR Input Fields</strong></P></td>
    </tr>
    <tr>
        <td align='right' width='55%'><strong><em>VPC API Version: </em></strong></td>
        <td width='45%'><?php echo $version?></td>
    </tr>
    <tr class="shade">                  
        <td align='right'><strong><em>Command: </em></strong></td>
        <td><?php echo $command?></td>
    </tr>
    <tr>
        <td align='right'><strong><em>Merchant Transaction Reference: </em></strong></td>
        <td><?php echo $merchTxnRef?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>Merchant ID: </em></strong></td>
        <td><?php echo $merchantID?></td>
    </tr>   
<?      
// Only display the following fields if not an error condition
if (strtoupper($drExists) != "N") {
?>
    <tr>
        <td colspan='2' align='center'><DIV class="bl"><hr/>
            Fields below are for a Standard Transaction.</DIV></td>
    </tr>
    
    <tr class="title">
        <td colspan="2" height="25"><P><strong>&nbsp;Standard Transaction Receipt Fields</strong></P></td>
    </tr>
  
    <tr>                  
        <td align='right'><strong><em>Order Information: </em></strong></td>
        <td><?php echo $orderInfo?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>Purchase Amount: </em></strong></td>
        <td><?php echo $amount?></td>
    </tr>
  
    <tr>                  
        <td align='right'><strong><em>VPC Transaction Response Code: </em></strong></td>
        <td><?php echo $txnResponseCode?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>Transaction Response Code Description: </em></strong></td>
        <td><?php echo getResponseDescription($txnResponseCode)?></td>
    </tr>
    <tr>                  
        <td align='right'><strong><em>Message: </em></strong></td>
        <td><?php echo $message?></td>
    </tr>

<? // only display the following fields if not an error condition
if ($txnResponseCode != "7" && $txnResponseCode != "No Value Returned" && strtoupper($drExists) == "Y") { ?>

    <tr class="shade">
        <td align='right'><strong><em>Receipt Number: </em></strong></td>
        <td><?php echo $receiptNo?></td>
    </tr>
    <tr>                  
        <td align='right'><strong><em>Transaction Number: </em></strong></td>
        <td><?php echo $transactionNo?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>Acquirer Response Code: </em></strong></td>
        <td><?php echo $acqResponseCode?></td>
    </tr>
    <tr>                  
        <td align='right'><strong><em>Bank Authorization ID: </em></strong></td>
        <td><?php echo $authorizeID?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>Batch Number: </em></strong></td>
        <td><?php echo $batchNo?></td>
    </tr>
    <tr>                  
        <td align='right'><strong><em>Card Type: </em></strong></td>
        <td><?php echo $cardType?></td>
    </tr>
      
    <tr>
        <td colspan='2' align='center'><DIV class="bl"'>Fields above are for Standard Transactions<BR /><hr/>
            Fields below are additional fields for extra functionality.</DIV></td>
    </tr>

<?        if ($amaTransaction) { ?>

    <tr class="title">
        <td colspan="2" height="25"><P><strong>&nbsp;Financial Transaction Fields</strong></P></td>
    </tr>
    <tr>
        <td align='right'><strong><em>Shopping Transaction Number: </em></strong></td>
        <td><?php echo $shopTransNo?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>Authorised Amount: </em></strong></td>
        <td><?php echo $authorisedAmount?></td>
    </tr>
    <tr>                
        <td align='right'><strong><em>Captured Amount: </em></strong></td>
        <td><?php echo $capturedAmount?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>Refunded Amount: </em></strong></td>
        <td><?php echo $refundedAmount?></td>
    </tr>
    <tr>                  
        <td align='right'><strong><em>Ticket Number: </em></strong></td>
        <td><?php echo $ticketNumber?></td>
    </tr>

<?        } else { ?>

      
    <tr class="title">
        <td colspan="2" height="25"><P><strong>&nbsp;3-D Secure Fields</strong></P></td>
    </tr>
    <tr>   
        <td align='right'><strong><em>Unique 3DS transaction identifier: </em></strong></td>
        <td class='red'><?php echo $xid?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>3DS Cardholder Authentication Verification Value: </em></strong></td>
        <td class='red'><?php echo $token?></td>
    </tr>
    <tr>    
        <td align='right'><strong><em>3DS Electronic Commerce Indicator: </em></strong></td>
        <td class='red'><?php echo $acqECI?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>3DS Authentication Scheme: </em></strong></td>
        <td class='red'><?php echo $verType?></td>
    </tr>
    <tr>    
        <td align='right'><strong><em>3DS Security level used in the AUTH message: </em></strong></td>
        <td class='red'><?php echo $verSecurLevel?></td>
    </tr>
    <tr class="shade">
        <td align='right'><strong><em>3DS CardHolder Enrolled: </strong><br/>
        <font size='1'>Takes values: <strong>Y</strong> - Yes <strong>N</strong> - No</em></td>
        <td class='red'><?php echo $enrolled?></td>
    </tr>
    <tr>
        <td align='right'><em><strong>Authenticated Successfully: </strong><br/>
        <font size='1'>Only returned if CardHolder Enrolled = <strong>Y</strong>. 
        Takes values: <br/><strong>Y</strong> - Yes <strong>N</strong> - No <strong>A</strong> - Attempted to Check 
        <strong>U</strong> - Unavailable for Checking</font></em></td>
        <td class='red'><?php echo $authStatus?></td>
    </tr>
    <tr class="shade">    
        <td align='right'><strong><em>Payment Server 3DS Authentication Status Code: </em></strong></td>
        <td class='green'><?php echo $verStatus?></td>
    </tr>
    <tr>    
        <td align='right'><em><strong>3DS Authentication Status Code Description: </strong></em></td>
        <td class='green'><?php echo getStatusDescription($verStatus)?></td>
    </tr>
    <tr>
	<td align="right"><strong><i>Hash Validated Correctly: </i></strong></td>
        <td><?php echo $hashValidated;?></td>
    </tr>
<?      }
    }
} ?>
</table>

</body>
</html>

<?php 
// End Processing

//  ----------------------------------------------------------------------------

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
   
// If input is null, returns string "No Value Returned", else returns value corresponding to given key
function null2unknown($map, $key) {
    if (array_key_exists($key, $map)) {
        if (!is_null($map[$key])) {
            return $map[$key];
        }
    } 
    return "No Value Returned";
} 
    
//  ----------------------------------------------------------------------------
