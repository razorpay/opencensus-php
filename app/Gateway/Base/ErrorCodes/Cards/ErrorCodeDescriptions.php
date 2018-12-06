<?php

namespace RZP\Gateway\Base\ErrorCodes\Cards;

use RZP\Gateway\Base;

class ErrorCodeDescriptions extends Base\ErrorCodes\BaseCodeDescriptions
{
    public static $errorDescriptionMap = [
        '01' => 'Refer to Issuer',
        '02' => 'Refer to Issuer, special',
        '03' => 'No Merchant',
        '04' => 'Pick Up Card',
        '05' => 'Do Not Honour',
        '06' => 'Error',
        '07' => 'Pick Up Card, Special',
        '08' => 'Honour With Identification',
        '09' => 'Request In Progress',
        '10' => 'Approved For Partial Amount',
        '11' => 'Approved, VIP',
        '12' => 'Invalid Transaction',
        '13' => 'Invalid Amount',
        '14' => 'Invalid Card Number',
        '15' => 'No Issuer',
        '16' => 'Approved, Update Track 3',
        '19' => 'Re-enter Last Transaction',
        '21' => 'No Action Taken',
        '22' => 'Suspected Malfunction',
        '23' => 'Unacceptable Transaction Fee',
        '25' => 'Unable to Locate Record On File',
        '30' => 'Format Error',
        '31' => 'Bank Not Supported By Switch',
        '33' => 'Expired Card, Capture',
        '34' => 'Suspected Fraud, Retain Card',
        '35' => 'Card Acceptor, Contact Acquirer, Retain Card',
        '36' => 'Restricted Card, Retain Card',
        '37' => 'Contact Acquirer Security Department, Retain Card',
        '39' => 'No Credit Account',
        '41' => 'Lost Card',
        '42' => 'No Universal Account',
        '43' => 'Stolen Card',
        '51' => 'Insufficient Funds',
        '54' => 'Expired Card',
        '56' => 'No Card Record',
        '57' => 'Function Not Permitted to Cardholder',
        '59' => 'Suspected Fraud',
        '60' => 'Acceptor Contact Acquirer',
        '62' => 'Restricted Card',
        '63' => 'Security Violation',
        '64' => 'Original Amount Incorrect',
        '66' => 'Acceptor Contact Acquirer, Security',
        '67' => 'Capture Card',
        '82' => 'CVV Validation Error',
        '90' => 'Cutoff In Progress',
        '91' => 'Card Issuer Unavailable',
        '92' => 'Unable To Route Transaction',
        '93' => 'Cannot Complete, Violation Of The Law',
        '94' => 'Duplicate Transaction',
        '96' => 'System Error'
    ];
}
