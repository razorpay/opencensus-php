<?php

namespace RZP\Gateway\Base;

use RZP\Error\Error;
use \RZP\Error\ErrorCode;

class ErrorCodes
{
//    const SUCCESS_CODES = ["00", "08"];

    public static $globalErrorCodes = [
        "01" => "Refer to Issuer",
        "02" => "Refer to Issuer, special",
        "03" => "No Merchant",
        "04" => "Pick Up Card",
        "05" => "Do Not Honour",
        "06" => "Error",
        "07" => "Pick Up Card, Special",
        "08" => "Honour With Identification",
        "09" => "Request In Progress",
        "10" => "Approved For Partial Amount",
        "11" => "Approved, VIP",
        "12" => "Invalid Transaction",
        "13" => "Invalid Amount",
        "14" => "Invalid Card Number",
        "15" => "No Issuer",
        "16" => "Approved, Update Track 3",
        "19" => "Re-enter Last Transaction",
        "21" => "No Action Taken",
        "22" => "Suspected Malfunction",
        "23" => "Unacceptable Transaction Fee",
        "25" => "Unable to Locate Record On File",
        "30" => "Format Error",
        "31" => "Bank Not Supported By Switch",
        "33" => "Expired Card, Capture",
        "34" => "Suspected Fraud, Retain Card",
        "35" => "Card Acceptor, Contact Acquirer, Retain Card",
        "36" => "Restricted Card, Retain Card",
        "37" => "Contact Acquirer Security Department, Retain Card",
        "39" => "No Credit Account",
        "41" => "Lost Card",
        "42" => "No Universal Account",
        "43" => "Stolen Card",
        "51" => "Insufficient Funds",
        "54" => "Expired Card",
        "56" => "No Card Record",
        "57" => "Function Not Permitted to Cardholder",
        "59" => "Suspected Fraud",
        "60" => "Acceptor Contact Acquirer",
        "62" => "Restricted Card",
        "63" => "Security Violation",
        "64" => "Original Amount Incorrect",
        "66" => "Acceptor Contact Acquirer, Security",
        "67" => "Capture Card",
        "82" => "CVV Validation Error",
        "90" => "Cutoff In Progress",
        "91" => "Card Issuer Unavailable",
        "92" => "Unable To Route Transaction",
        "93" => "Cannot Complete, Violation Of The Law",
        "94" => "Duplicate Transaction",
        "96" => "System Error"
        ];

    public static $map = [
        "01" => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        "02" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        "03" => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        "04" => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        "05" => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK,
        "06" => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        "07" => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        //"08" => "Honour With Identification",
        "09" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE,
        "10" => ErrorCode::BAD_REQUEST_PAYMENT_PARTIAL_AMOUNT_APPROVED,
//        "11" => "Approved, VIP",
        "12" => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_FORMAT,
        "13" => ErrorCode::BAD_REQUEST_INVALID_TRANSACTION_AMOUNT,
        "14" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        "15" => ErrorCode::GATEWAY_ERROR_BANK_NOT_SUPPORTED_BY_SWITCH,
//        "16" => "Approved, Update Track 3",
        "19" => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        "21" => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        "22" => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        "23" => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        "25" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID,
        "30" => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_FORMAT,
        "31" => ErrorCode::GATEWAY_ERROR_BANK_NOT_SUPPORTED_BY_SWITCH,
        "33" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_EXPIRED,
        "34" => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        "35" => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK, // GATEWAY_ERROR_TRANSACTION_DECLINED
        "36" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_NOT_PERMITTED_TXN, // GATEWAY_ERROR_TRANSACTION_DECLINED
        "37" => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK, // GATEWAY_ERROR_TRANSACTION_DECLINED
        "39" => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        "41" => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST, // GATEWAY_ERROR_TRANSACTION_DECLINED
        "42" => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        "43" => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST, // GATEWAY_ERROR_TRANSACTION_DECLINED
        "51" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        "54" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_EXPIRED,
        "56" => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER,
        "57" => ErrorCode::BAD_REQUEST_ACTION_NOT_APPROVED,
        "59" => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        "60" => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        "62" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_NOT_PERMITTED_TXN,
        "63" => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        "64" => ErrorCode::BAD_REQUEST_TRANSACTION_AMOUT_GREATER_THAN_REGISTERED_AMOUNT, // GATEWAY_ERROR_AMOUNT_TAMPERED
        "66" => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        "67" => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,  // BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK
        "82" => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        "90" => ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED, // BAD_REQUEST_UNABLE_TO_AUTHORIZE_PAYMENT
        "91" => ErrorCode::BAD_REQUEST_CARD_ISSUING_BANK_UNAVAILABLE, // should be GATEWAY_ERROR_CARD_ISSUING_BANK_UNAVAILABLE
        "92" => ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_FOR_TEST_ACCOUNT,
        "93" => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        "94" => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        "96" => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        "default" => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ];

    public static function getErrorCode($code)
    {
        if (isset($code, self::$mappedErrorCodes))
        {
            return self::$mappedErrorCodes[$code];
        }

        return self::$mappedErrorCodes['default'];
    }
}
