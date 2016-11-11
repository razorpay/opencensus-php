<?php

namespace RZP\Gateway\AxisMigs;

class VerStatus
{
    protected static $codes = [
        'Y' => 'The cardholder was successfully authenticated.',
        'E' => 'The cardholder is not enrolled.',
        'N' => 'The cardholder was not verified.',
        'U' => 'The cardholder\'s Issuer was unable to authenticate due to a system error at the Issuer.',
        'F' => 'An error exists in the format of the request from the merchant. For example, the request did not contain all required fields, or the format of some fields was invalid.',
        'A' => 'Authentication of your Merchant ID and Password to the Directory Server Failed.',
        'D' => 'Error communicating with the Directory Server, for example, the Payment Server could not connect to the directory server or there was a versioning mismatch.',
        'C' => 'The card type is not supported for authentication.',
        'M' => 'This indicates that attempts processing was used. Verification is marked with status M – ACS attempts processing used. Payment is performed with authentication. Attempts is when a cardholder has successfully passed the directory server but decides not to continue with the authentication process and cancels.',
        'S' => 'The signature on the response received from the Issuer could not be validated. This should be considered a failure.',
        'T' => 'ACS timed out.',
        'P' => 'Error parsing input from Issuer.',
        'I' => 'Internal Payment Server system error. This could be caused by a temporary DB failure or an error in the security module or by some error in an internal system.',
    ];

    protected static $eci = [
        'Y' => '05',
        'E' => '06',
        'N' => '-',
        'U' => '07',
        'F' => '-',
        'A' => '07',
        'D' => '07',
        'C' => '-',
        'M' => '06',
        'S' => '-',
        'T' => '-',
        'P' => '-',
        'I' => '?',
    ];

    protected static $secureCodeSli = [
        'Y' => '2',
        'E' => '1',
        'N' => '-',
        'U' => '1',
        'F' => '-',
        'A' => '0',
        'D' => '1',
        'C' => '-',
        'M' => '1',
        'S' => '-',
        'T' => '-',
        'P' => '-',
        'I' => '?',
    ];
}
