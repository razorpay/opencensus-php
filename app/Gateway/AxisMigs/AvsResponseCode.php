<?php
/**
 * Created by PhpStorm.
 * User: mukulacharya
 * Date: 06/09/18
 * Time: 11:59 AM
 */

namespace RZP\Gateway\AxisMigs;

use \RZP\Error\ErrorCode;


class AvsResponseCode
{
    public static $avsResponseCodes = [
        "A" => "Address matches, postal code does not.",
        "B" => "Visa only: Street address match. Postal code not verified because of incompatible 
                formats. (Acquirer sent both street address and postal code).",
        "C" => "Visa only: Street address and postal code not verified because of incompatible formats. 
                (Acquirer sent both street address and postal code).",
        "D" => "Visa: Street address and postal code match. Address and zip match. Amex: Card Member Name 
                incorrect, Billing Postal Code match. Z 5-digit zip match",
        "E" => "Amex: Card Member Name incorrect, Billing Address and Postal Code match.",
        "F" => "Visa: Street address and Postal Code match. Applies to U.K. only. Amex: Card Member Name 
                incorrect, Billing Address matches. A Address match only.",
        "G" => "Visa only. Non-AVS participant outside the U.S.; address not verified for international 
                transaction.",
        "I" => "Visa only. Address information not verified for international transaction.",
        "K" => "Amex: Card Member Name matches.",
        "L" => "Amex: Card Member Name and Billing Postal Code match.",
        "M" => "Visa: Street addresses and Postal Codes match. Amex: Card Member Name, Billing Address and 
                Postal Code match.",
        "N" => "Neither address nor postal code matches.",
        "O" => "Amex: Card Member Name and Billing Address match.",
        "P" => "Visa only. Postal Codes match. Street address not verified because of incompatible formats. 
                (Acquirer sent both street address and postal code).",
        "R" => "Retry, system is unable to process.",
        "S" => "AVS currently not supported. Amex: SE not allowed AAV function.",
        "U" => "No data from Issuer/authorisation system.",
        "W" => "For U.S. addresses, 9-digit postal code matches, address does not; for address outside the 
                U.S., postal code matches, address does not.",
        "X" => "For U.S. addresses, 9-digit Postal Code and Address match; for address outside the U.S., 
                Postal Code and Address match.",
        "Y" => "For U.S. addresses, 5-digit Postal Code and Address match.",
        "Z" => "For U.S. addresses, 5-digit Postal Code matches, Address does not.",
    ];

    public static $map = [
        "A" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "B" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "C" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "D" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "E" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "F" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "G" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "I" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "K" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "L" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "M" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "N" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "O" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "P" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "R" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "S" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "U" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "W" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "X" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "Y" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
        "Z" => ErrorCode::BAD_REQUEST_CARD_AVS_FAILED,
    ];
}