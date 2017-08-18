<?php

namespace RZP\Gateway\Cybersource;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $reasonCodes = array(
        100 => 'Successful transaction',
        101 => 'The request is missing one or more fields',
        102 => 'One or more fields in the request contains invalid data',
        104 => 'ThemerchantReferenceCode sent with this authorization request matches the merchantReferenceCode of another authorization request that you sent in the last 15 minutes.',
        110 => 'Partial amount was approved',
        150 => 'General system failure.',
        151 => 'The request was received but there was a server timeout. This error does not include timeouts between the client and the server.',
        152 => 'The request was received, but a service did not finish running in time.',
        200 => 'The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the Address Verification Service (AVS) check.',
        201 => 'The issuing bank has questions about the request. You do not receive an authorization code programmatically, but you might receive one verbally by calling the processor.',
        202 => 'Expired card. You might also receive this if the expiration date you provided does not match the date the issuing bank has on file.',
        203 => 'General decline of the card. No other information provided by the issuing bank.',
        204 => 'Insufficient funds in the account.',
        205 => 'Stolen or lost card.',
        207 => 'Issuing bank unavailable.',
        208 => 'Inactive card or card not authorized for card-not-present transactions.',
        209 => 'American Express Card Identification Digits (CID) did not match.',
        210 => 'The card has reached the credit limit.',
        211 => 'Invalid Card Verification Number (CVN).',
        220 => 'Generic Decline.',
        221 => 'The customer matched an entry on the processor\'s negative file.',
        222 => 'Customer\'s account is frozen',
        230 => 'The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the card verification number (CVN) check.',
        231 => 'Invalid account number',
        232 => 'The card type is not accepted by the payment processor.',
        233 => 'General decline by the processor.',
        234 => 'There is a problem with your CyberSource merchant configuration.',
        235 => 'The requested amount exceeds the originally authorized amount. Occurs, for example, if you try to capture an amount larger than the original authorization amount.',
        236 => 'Processor failure.',
        237 => 'The authorization has already been reversed.',
        238 => 'The transaction has already been settled.',
        239 => 'The requested transaction amount must match the previous transaction amount.',
        240 => 'The card type sent is invalid or does not correlate with the credit card number.',
        241 => 'The referenced request id is invalid for all follow-on transactions.',
        242 => 'The request ID is invalid.',
        243 => 'The transaction has already been settled or reversed.',
        246 => 'The capture or credit is not voidable because the capture or credit information has already been submitted to your processor. Or, you requested a void for a type of transaction that cannot be voided.',
        247 => 'You requested a credit for a capture that was previously voided.',
        248 => 'The boleto request was declined by your processor.',
        250 => 'The request was received, but there was a timeout at the payment processor.',
        251 => 'The Pinless Debit card\'s use frequency or maximum amount per use has been exceeded.',
        254 => 'Account is prohibited from processing stand-alone refunds.',

        400 => 'Fraud score exceeds threshold.',

        450 => 'Apartment number missing or not found.',
        451 => 'Insufficient address information.',
        452 => 'House/Box number not found on street.',
        453 => 'Multiple address matches were found.',
        454 => 'P.O. Box identifier not found or out of range.',
        455 => 'Route service identifier not found or out of range.',
        456 => 'Street name not found in Postal code.',
        457 => 'Postal code not found in database.',
        458 => 'Unable to verify or correct address.',
        459 => 'Multiple addres matches were found (international)',
        460 => 'Address match not found (no reason given)',

        461 => 'Unsupported character set',
        475 => 'The cardholder is enrolled in Payer Authentication. Please authenticate the cardholder before continuing with the transaction.',
        476 => 'Encountered a Payer Authentication problem. Payer could not be authenticated.',
        480 => 'The order is marked for review by Decision Manager',
        481 => 'The order has been rejected by Decision Manager',
        520 => 'The authorization request was approved by the issuing bank but declined by CyberSource based on your Smart Authorization settings.',

        // Action - Reject customer order
        700 => 'The customer matched the Denied Parties List',
        701 => 'Export bill_country/ship_country match',
        702 => 'Export email_country match',
        703 => 'Export hostname_country/ip_country match'
    );

    public static $errorCodeMap = array(
        101 => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        102 => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        104 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        110 => ErrorCode::BAD_REQUEST_PAYMENT_PARTIAL_AMOUNT_APPROVED,
        150 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        151 => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
        152 => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
        200 => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        201 => ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED,
        202 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE,
        203 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        204 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        205 => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        207 => ErrorCode::BAD_REQUEST_CARD_ISSUING_BANK_UNAVAILABLE,
        208 => ErrorCode::BAD_REQUEST_CARD_INACTIVE,
        209 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE,
        210 => ErrorCode::BAD_REQUEST_CARD_CREDIT_LIMIT_REACHED,
        211 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        220 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        221 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        222 => ErrorCode::BAD_REQUEST_CARD_FROZEN,
        230 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        231 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE,
        232 => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
        233 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        234 => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
        235 => ErrorCode::GATEWAY_ERROR_CAPTURE_GREATER_THAN_AUTH,
        236 => ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED,
        237 => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_REFUNDED,
        238 => ErrorCode::GATEWAY_ERROR_PAYMENT_ALREADY_SETTLED,
        239 => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
        240 => ErrorCode::GATEWAY_ERROR_UNSUPPORTED_CARD_NETWORK,
        241 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        242 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        243 => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_REFUNDED,
        246 => ErrorCode::GATEWAY_ERROR_PAYMENT_VOID_FAILED,
        247 => ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED,
        248 => ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED,
        250 => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
        251 => ErrorCode::BAD_REQUEST_CARD_DAILY_LIMIT_REACHED,
        254 => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,

        400 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,

        450 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        451 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        452 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        453 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        454 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        455 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        456 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        457 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ZIP,
        458 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        459 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        460 => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,

        461 => ErrorCode::BAD_REQUEST_UNSUPPORTED_CHARACTER_SET,
        476 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        480 => ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK,
        481 => ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED,
        520 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,

        700 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        701 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        702 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        703 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
    );

    public static function getDescription($code)
    {
        if (isset(self::$reasonCodes[$code]))
        {
            return self::$reasonCodes[$code];
        }

        return 'Payment failed';
    }

    public static function getMappedCode($code)
    {
        if (isset(self::$errorCodeMap[$code]))
        {
            return self::$errorCodeMap[$code];
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
