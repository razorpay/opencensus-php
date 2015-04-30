<?php

namespace Gateway\AxisMigs;

use EE\Error;
use Gateway\AxisMigs;

class Error
{
    public static $errorMessages = array(
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
        'C' => 'Transaction Cancelled',
        'D' => 'Deferred transaction has been received and is awaiting processing',
        'F' => '3D Secure Authentication failed',
        'I' => 'Card Security Code verification failed',
        'L' => 'Shopping Transaction Locked (Please try the transaction again later)',
        'N' => 'Cardholder is not enrolled in Authentication Scheme',
        'P' => 'Transaction has been received by the Payment Adaptor and is being processed',
        'R' => 'Transaction was not processed - Reached limit of retry attempts allowed',
        'S' => 'Duplicate SessionID (OrderInfo)',
        'T' => 'Address Verification Failed',
        'U' => 'Card Security Code Failed',
        'V' => 'Address Verification and Card Security Code Failed',
        '?' => 'Transaction status is unknown',
    );

//    'default' => 'Unable to be determined',
}
