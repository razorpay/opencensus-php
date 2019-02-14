<?php

namespace RZP\Gateway\AxisMigs\ErrorCodes;

use RZP\Gateway\Base\ErrorCodes\Cards;

class ErrorCodeDescriptions extends Cards\ErrorCodeDescriptions
{
    public static $vpcErrorDescriptionMap = [
        '5000' => 'Undefined error',
        '5001' => 'Invalid Digital Order',
        '5002' => 'Invalid Digital Order: not enough fields',
        '5003' => 'Invalid Digital Order: too many fields',
        '5004' => 'Invalid Digital Order: invalid session ID',
        '5005' => 'Invalid Digital Order: invalid Merchant Id',
        '5006' => 'Invalid Digital Order: invalid purchase amount',
        '5007' => 'Invalid Digital Order: invalid locale',
        '5008' => 'Invalid Digital Order: outdated version',
        '5009' => 'Invalid Digital Order: bad or too many Transaction Request parameters.',
        /**
         * 5009 - Invalid Digital Order: bad or too many Transaction Request parameters
         * It could be one of the following:
         *   1.  Invalid Digital Order: Invalid PAN Entry Mode
         *   2.  Invalid Digital Order: Invalid PIN Entry Capability
         *   3.  Bad Credit Payment Type
         *   4.  Bad Account Balance Type
         *   5.  Unsupported Transaction Type
         *   6.  Invalid Digital Order: Invalid Payment Method
         *   7.  Invalid Digital Order: Invalid PIN field
         *   8.  Invalid Digital Order: Invalid KSN field
         *   9.  Invalid Digital Order: Invalid STAN field
         *   10. Invalid Digital Order: Invalid PhysicalTerminalId field
         *   11. Invalid Digital Order: Invalid POSEntryMode field
         *   12. PIN Entry Capability Terminal Cannot Accept PIN
         *   13. PIN Entry Capability Terminal PIN pad down
         *   14. Authorisation Code must be provided
         *   15. Authorisation Code must be numeric and 1 to 6 characters in length'
         */
        '5010' => 'Bad DCC Base Amount',
        '5011' => 'Bad DCC Base Currency',
        '5012' => 'Bad DCC Exchange Rate',
        '5013' => 'Bad DCC Offer State',
        '5014' => 'DCC Offer State Unsupported',
        '5015' => 'Missing or Invalid Currency',
        '5016' => 'Missing or Invalid Merchant Transaction Reference',
        '5020' => 'Invalid Digital Receipt',
        '5021' => 'Invalid Digital Receipt: not enough fields',
        '5022' => 'Invalid Digital Receipt: too many fields',
        '5023' => 'Invalid Digital Receipt: invalid session ID',
        '5024' => 'Invalid Digital Receipt: invalid Merchant Id',
        '5025' => 'Invalid Digital Receipt: invalid purchase amount',
        '5026' => 'Invalid Digital Receipt: invalid locale',
        '5027' => 'Error in generating Digital Receipt ID',
        '5028' => 'Invalid Digital Receipt Delivery URL',
        '5029' => 'Invalid Digital Receipt Delivery IO',
        '5030' => 'Invalid Transaction log string',
        '5031' => 'Invalid Transaction log string: not enough fields',
        '5032' => 'Invalid Transaction log string: too many fields',
        '5033' => 'Invalid Transaction log string: invalid purchase amount',
        '5034' => 'Invalid Transaction log string: invalid locale',
        '5035' => 'Transaction Log File error',
        '5040' => 'Invalid QsiFinTrans message',
        '5041' => 'Unsupported acquirer',
        '5042' => 'Unsupported transport',
        '5043' => 'Unsupported message format',
        '5044' => 'Invalid Merchant transaction mode',
        '5045' => 'Unsupported transaction counter',
        '5046' => 'SecureCGIParam verification of digital signature failed',
        '5047' => 'Failed to read a QsiSigner object back from a serialized file!',
        '5048' => 'Failed to create a DCOM object',
        '5049' => 'Receipt is invalid.',
        '5050' => 'Invalid Permission',
        '5051' => 'Unsatisfied DLL link error',
        '5052' => 'Invalid Merchant Id',
        '5053' => 'Transmission error from QSIFinTrans',
        '5054' => 'Parser error',
        '5055' => 'Acquirer Response Error',
        '5056' => 'Trace file I/O error',
        '5057' => 'Invalid cookie',
        '5058' => 'RMI exception',
        '5059' => 'Invalid session',
        '5060' => 'Invalid locale',
        '5061' => 'Unsupported payment method',
        '5065' => 'Runtime exception',
        '5066' => 'Bad parameter name or value',
        '5070' => 'File backup error',
        '5071' => 'File save error',
        '5072' => 'File IO error',
        '5073' => 'File not found error',
        '5074' => 'File not found',
        '5080' => 'SQL Error',
        '5081' => 'SQL Error : Cannot locate the database',
        '5082' => 'SQL Error : Cannot connect to the database',
        '5083' => 'SQL Error : Incorrect row count',
        '5084' => 'SQL Error : Invalid value format',
        '5085' => 'SQL Error : Bad line count',
        '5086' => 'Duplicate primary agent',
        '5087' => 'Unknown database type',
        '5090' => 'Illegal user name',
        '5091' => 'Illegal password error',
        '5101' => 'Could not create and load the specified KeyStore object. If you are using a QSIDB
                   KeyStore the database connection may have failed',
        '5103' => 'Could not create the specified javax.crypto.Cipher object.',
        // You may not have a
        // provider installed to create this type of Cipher object or the Cipher object
        // that is specified in your config file is incorrect',
        '5104' => 'Error in call to javax.crypto.Cipher.doFinal. Either the input was too large or the
                   padding was bad',
        '5106' => 'The Message type specified is not supported.',
        // Check the com.qsipayments.technology.security.MessageCrypto.properties
        // file to ensure that the MsgType is valid',
        '5108' => 'The message received has a bad format',
        '5109' => 'Error verifying signature',
        '5110' => 'Error creating a signature',
        '5161' => 'Customer Reference too long',
        '5175' => 'Card track data exceeded the allowed lengths',
        '5120' => 'Unable to generate new keys',
        '5121' => 'Try to access an invalid key file',
        '5122' => 'Not able to store the security keys',
        '5123' => 'Not able to retrieve the security keys',
        '5124' => 'Encryption format invalid for Digital Order',
        '5125' => 'Encryption signature invalid for Digital Order',
        '5126' => 'Invalid transaction mode',
        '5127' => 'Unable to find user keys',
        '5128' => 'Bad key Id',
        '5129' => 'Credit Card No Decryption failed',
        '5130' => 'Credit Card Encryption failed',
        '5131' => 'Problem with Crypto Algorithm',
        '5132' => 'Key used is invalid',
        '5133' => 'Signature Key used is invalid',
        '5134' => 'RSA Decrypt Failed',
        '5135' => 'RSA Encrypt Failed',
        '5136' => 'The keys stored in the keyfile given to SecureCGIParam was corrupt or one of the
                   keys is invalid',
        '5137' => 'The private key stored in the keyfile given to SecureCGIParam was corrupt or one
                   of the keys is invalid',
        '5138' => 'The public key stored in the keyfile given to SecureCGIParam was corrupt or one
                   of the keys is invalid',
        '5140' => 'Invalid Acquirer',
        '5141' => 'Generic error for a financial transaction',
        '5142' => 'Generic reconciliation error for a transaction',
        '5143' => 'Transaction counter exceeds predefined value',
        '5144' => 'Generic terminal pooling error',
        '5145' => 'Generic terminal error',
        '5146' => 'Terminal near full',
        '5147' => 'Terminal Full',
        '5148' => 'Attempted to call a method that required a reconciliation to be in progress but
                   this was not the case',
        '5150' => 'Invalid credit card: incorrect issue number length',
        '5151' => 'Invalid Credit Card Specifications',
        '5152' => 'Invalid Credit Card information contained in the database',
        '5153' => 'Invalid Card Number Length',
        '5154' => 'Invalid Card Number',
        '5155' => 'Invalid Card Number Prefix',
        '5156' => 'Invalid Card Number Check Digit',
        '5157' => 'Invalid Card Expiry Date',
        '5158' => 'Invalid Card Expiry Date Length',
        '5159' => 'Invalid Card Type',
        '5162' => 'Invalid Card Initialisation file',
        '5166' => 'Invalid Credit Card: incorrect secure code number length',
        '5170' => 'Unable to delete terminal',
        '5171' => 'Unable to create terminal',
        '5176' => 'Bad Card Track, invalid card track sentinels',
        '5185' => 'Invalid Acknowledgement',
        '5200' => 'Payment Client Creation Failed',
        '5201' => 'Creating Digital Order Failed',
        '5202' => 'Creating Digital Receipt Failed',
        '5204' => 'Executing Administration Capture Failed',
        '5205' => 'Executing Administration Refund Failed',
        '5206' => 'Executing Administration Void Capture Failed',
        '5207' => 'Executing Administration Void Refund Failed',
        '5208' => 'Executing Administration Financial Transaction History Failed',
        '5209' => 'Executing Administration Shopping Transaction History Failed',
        '5210' => 'PaymentClient Access to QueryDR Denied',
        '5220' => 'Executing Administration Reconciliation Failed',
        '5221' => 'Executing Administration Reconciliation Item Detail Failed',
        '5222' => 'Executing Administration Reconciliation History Failed',
        '5230' => 'Retrieving Digital Receipt Failed',
        '5231' => 'Retrieved Digital Receipt Error',
        '5232' => 'Digital Order Command Error',
        '5233' => 'Digital Order Internal Error',
        '5234' => 'MOTO Internal Error',
        '5235' => 'Digital Receipt Internal Error',
        '5336' => 'Administration Internal Error',
        '5400' => 'Digital Order is null',
        '5401' => 'Null Parameter',
        '5402' => 'Command Missing',
        '5403' => 'Digital Order is null',
        '5408' => 'The full amount of the transaction has already been captured or voided',
        '5410' => 'Unknown Field',
        '5411' => 'Unknown Administration Method',
        '5412' => 'Invalid Field',
        '5413' => 'Missing Field',
        '5414' => 'Capture Error',
        '5415' => 'Refund Error',
        '5416' => 'VoidCapture Error',
        '5417' => 'VoidRefund Error',
        '5418' => 'Financial Transaction History Error',
        '5419' => 'Shopping Transaction History Error',
        '5420' => 'Reconciliation Error',
        '5421' => 'Reconciliation Detail Error',
        '5422' => 'Reconciliation History Error',
        '5423' => 'Bad User Name or Password',
        '5424' => 'Administration Internal Error',
        '5425' => 'Invalid Recurring Transaction Number',
        '5426' => 'Invalid Permission',
        '5427' => 'Purchase Error',
        '5428' => 'VoidPurchase Error',
        '5429' => 'QueryDR Error',
        '5430' => 'Missing Field',
        '5431' => 'Invalid Field Digital.TRANS_NO must be provided to indicate which existing order this
                   transaction is to be performed against',
        '5432' => 'Internal Error',
        '5433' => 'Invalid Permission',
        '5434' => 'Deferred Payment service currently unavailable',
        '5435' => 'Max No of Deferred Payment reached',
        '5436' => 'Invalid recurring transaction number',
        '5450' => 'DirectPaymentSend: Null digital order',
        '5451' => 'DirectPaymentSend: Internal error',
        '5500' => 'Error in card detail',
        '5501' => 'Errors exists in card details',
        '5600' => 'Transaction retry count exceeded',
        '5601' => 'Instantiation of AcquirerController for this transaction failed.',
        '5602' => 'An I/O error occurred',
        '5603' => 'Could not get a valid terminal',
        '5604' => 'Unable to create the ProtocolReconciliationController for the protocol',
        '5661' => 'Illegal Acquirer Object Exception',
        '5670' => 'Message Exception',
        '5671' => 'Malformed Message Exception',
        '5672' => 'Illegal Message Object Exception',
        '5680' => 'Transport Exception',
        '5681' => 'Transport type not found',
        '5682' => 'Transport connection error',
        '5683' => 'Transport IO error',
        '5684' => 'Illegal Transport Object Exception',
        '5690' => 'Permanent Socket Transport connected',
        '5691' => 'Permanent Socket Transport JII class exception',
        '5692' => 'Permanent Socket Transport mismatched message received',
        '5693' => 'Permanent Socket Transport malformed message received',
        '5694' => 'Permanent Socket Transport unavailable',
        '5695' => 'Permanent Socket Transport disconnected',
        '5696' => 'The connection has been closed prematurely',
        '5730' => 'Host Socket unavailable',
        '5750' => 'Message header not identified',
        '5751' => 'Message length field was invalid',
        '5752' => 'Start of text marker (STX) not found where expected',
        '5753' => 'End of text marker (ETX) not found where expected',
        '5754' => 'Message checksum (LRC) did not match',
        '5800' => 'Init service started',
        '5801' => 'Init service stopped',
        '5802' => 'Invalid entry',
        '5803' => 'Duplicate entry',
        '5804' => 'Parse error',
        '5805' => 'Executing task',
        '5806' => 'Cannot execute task',
        '5807' => 'Terminating task',
        '5808' => 'Task killed',
        '5809' => 'Respawning task',
        '5810' => 'Cron service started',
        '5811' => 'Cron service stopped',
        '5812' => 'Parse error',
        '5813' => 'Invalid entry',
        '5910' => 'Null pointer caught',
        '5911' => 'URL Decode Exception occurred',
        '5930' => 'Invalid card type for excessive refunds',
        '5931' => 'Agent not authorized to perform excessive refunds for this amount',
        '5932' => 'Too many excessive refunds apply to this shopping transaction already',
        '5933' => 'Merchant agent is not authorized to perform excessive refunds',
        '5934' => 'Merchant is not authorized to perform excessive refunds',
        '5935' => 'Merchant cannot perform excessive refunds due to its transaction type',
        '6010' => 'Bad format in Rulefile',
        '6100' => 'Invalid host name',
        '7000' => 'XML parser [Fatal Error]',
        '7001' => 'XML parser [Error]',
        '7002' => 'XML parser [Warning]',
        '7003' => 'XML Parameter is invalid',
        '7004' => 'XML Parameter had an invalid index. Check input .html file',
        '7005' => 'XML [Bad Provider Class]',
        '7050' => 'SleepTimer: Time value is not in a valid format (ignored this time value)',
        '7100' => 'No valid times and/or interval specified in StatementProcessing.properties file.
                   Execution terminated',
        '7101' => 'Status file for this data file was never created – deleting',
        '7102' => 'Error loading Statement.properties file',
        '7104' => 'Can’t find file',
        '7106' => 'IOException thrown attempting to create or write to file',
        '7107' => 'Overwriting file',
        '7108' => 'SecurityException thrown when attempting to create output file',
        '7109' => 'Invalid Merchant Id. This Advice element will not be processed',
        '7110' => 'Can’t create file name from the given date string',
        '7111' => 'Duplicate Advice element found in input document and skipped. Check input document',
        '7112' => 'Invalid payment type specified. This file will be skipped',
        '7113' => 'Null directory: can’t create output file',
        '7114' => 'Validation of input file provided by host failed',
        '7120' => 'IOException thrown attempting to create or write to file',
        '7121' => 'IOException thrown while attempting to create a ZIP archive',
        '7122' => 'An inaccessible output directory was specified in the configuration file',
        '7200' => 'PRE Issue Id Error',
        '7201' => 'No Login User Object stored in session.',
        '7202' => 'Error Occurred while creating the merchant on the Payment Server.',
        '7203' => 'Logging out',
        '7204' => 'Error occurred while instantiating Payment.',
        '7205' => 'Error occurred while instantiating SSL Payment',
        '7207' => 'Error occurred while sending email',
        '7208' => 'Invalid Access. User is trying to access a page illegally.',
        '7209' => 'Invalid User Input.',
        '7300' => 'Error parsing meta data file',
        '7301' => 'Invalid field',
        '7302' => 'Field validator not present',
        '7303' => 'Validation of field failed',
        '7304' => 'Field not present in arbitrary data',
        '7305' => 'Mandatory field missing',
        '7306' => 'Date mask is invalid',
        '7307' => 'Error creating field validator',
        '7308' => 'Failed to update arbitrary data',
        '7400' => 'Invalid transaction type',
        '7500' => 'Record has changed since last read',
        '8000' => 'Invalid Local Tax Flag',
        '8001' => 'Local Tax Amount Equal to or Greater then Initial Transaction Amount',
        '8002' => 'Purchaser Postcode Too Long',
        '8003' => 'Invalid Local Tax Flag and Local Tax Flag Amount Combination',
        '8004' => 'Invalid Local Tax Amount',
        '8015' => 'Payment method must be EBT for a balance inquiry or Invalid Digital Order: Invalid PaymentMethod',
        '8016' => 'Invalid Digital Order: Invalid PIN field',
        '8017' => 'Invalid Digital Order: Invalid KSN field',
        '8019' => 'Invalid Digital Order: Invalid PhysicalTerminalID field',
        '8020' => 'Invalid Digital Order: Invalid POSEntryMode field',
        '8021' => 'Invalid Digital Order: Invalid AdditionalAmount field',
        '9000' => 'Acquirer did not respond',
        '9150' => 'Missing or Invalid Secure Hash',
        '9151' => 'Invalid Secure Hash Type, or Secure Hash Type not allowed for this merchant',
        '9152' => 'Missing or Invalid Access Code',
        '9153' => 'Request contains more than one instance of the same field',
        '9154' => 'General merchant configuration error preventing request from being processed',
        '9200' => 'Missing or Invalid Template Number',
        '9520' => 'Server is unable to process the request at the moment - please try later',
        'Timed out' => 'Timed Out',
        'Pending' => 'Pending',
    ];

    public static $txnErrorDescriptionMap = [
        '0' => 'Transaction Successful',
        '1' => 'Unknown Error',
        '2' => 'Bank Declined Transaction',
        '3' => 'No Reply from Bank',
        '4' => 'Expired Card',
        '5' => 'Insufficient Funds',
        '6' => 'Error Communicating with Bank',
        '7' => 'Payment Server System Error',
        '8' => 'Transaction Type Not Supported',
        '9' => 'Bank declined transaction (Do not contact Bank)',
        'A' => 'Transaction Aborted',
        'B' => 'Transaction was blocked by the Payment Server because it did not pass all risk checks.',
        'C' => 'Transaction Cancelled',
        'D' => 'Deferred transaction has been received and is awaiting processing',
        'E' => 'Transaction Declined - Refer to card issuer',
        'F' => '3D Secure Authentication failed',
        'I' => 'Card Security Code verification failed',
        'L' => 'Shopping Transaction Locked (Please try the transaction again later)',
        'N' => 'Cardholder is not enrolled in 3DSecure Authentication Scheme',
//        'P' => 'Transaction has been received by the Payment Adaptor and is being processed',
        'R' => 'Transaction was not processed - Reached limit of retry attempts allowed',
        'S' => 'Duplicate SessionID (OrderInfo)',
        'T' => 'Address Verification Failed',
        'U' => 'Card Security Code Failed',
        'V' => 'Address Verification and Card Security Code Failed',
        '?' => 'Transaction status is unknown',
        'Aborted' => 'Transaction Aborted',
    ];

    public static $avsErrorDescriptionMap = [
        'A' => 'Address matches, postal code does not.',
        'B' => 'Visa only: Street address match. Postal code not verified because of incompatible
                formats. (Acquirer sent both street address and postal code).',
        'C' => 'Visa only: Street address and postal code not verified because of incompatible formats.
                (Acquirer sent both street address and postal code).',
        'D' => 'Visa: Street address and postal code match. Address and zip match. Amex: Card Member Name
                incorrect, Billing Postal Code match. Z 5-digit zip match',
        'E' => 'Amex: Card Member Name incorrect, Billing Address and Postal Code match.',
        'F' => 'Visa: Street address and Postal Code match. Applies to U.K. only. Amex: Card Member Name
                incorrect, Billing Address matches. A Address match only.',
        'G' => 'Visa only. Non-AVS participant outside the U.S.; address not verified for international
                transaction.',
        'I' => 'Visa only. Address information not verified for international transaction.',
        'K' => 'Amex: Card Member Name matches.',
        'L' => 'Amex: Card Member Name and Billing Postal Code match.',
        'M' => 'Visa: Street addresses and Postal Codes match. Amex: Card Member Name, Billing Address and
                Postal Code match.',
        'N' => 'Neither address nor postal code matches.',
        'O' => 'Amex: Card Member Name and Billing Address match.',
        'P' => 'Visa only. Postal Codes match. Street address not verified because of incompatible formats.
                (Acquirer sent both street address and postal code).',
        'R' => 'Retry, system is unable to process.',
        'S' => 'AVS currently not supported. Amex: SE not allowed AAV function.',
        'U' => 'No data from Issuer/authorisation system.',
        'W' => 'For U.S. addresses, 9-digit postal code matches, address does not; for address outside the
                U.S., postal code matches, address does not.',
        'X' => 'For U.S. addresses, 9-digit Postal Code and Address match; for address outside the U.S.,
                Postal Code and Address match.',
        'Y' => 'For U.S. addresses, 5-digit Postal Code and Address match.',
        'Z' => 'For U.S. addresses, 5-digit Postal Code matches, Address does not.',
    ];

    public static $cscErrorDescriptionMap = [
        'M' => 'Valid or matched CSC', // This is a success case
        'S' => 'Merchant indicates CSC not present on card',
        'P' => 'CSC Not Processed',
        'U' => 'Card issuer is not registered and/or certified',
        'N' => 'Code invalid or not matched',
    ];

    public static function getRelevantGatewayErrorCode($errorFieldName, $content)
    {
        if ($errorFieldName === ErrorFields::VPC_MESSAGE)
        {
            return static::getVpcMessageErrorCode($content[$errorFieldName]);
        }

        return parent::getRelevantGatewayErrorCode($errorFieldName, $content);
    }

    public static function getVpcMessageErrorCode($code)
    {
        $gatewayCode = null;

        if (in_array($code, ['Pending', 'Timed out']) === true)
        {
            return $code;
        }

        $isMatched = preg_match('/[0-9]{4}/', $code, $matches);

        if ($isMatched === 1)
        {
            $gatewayCode = $matches[0];
        }

        return $gatewayCode;
    }
}
