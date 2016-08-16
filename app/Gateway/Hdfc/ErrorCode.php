<?php

namespace RZP\Gateway\Hdfc;

use RZP\Error;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Payment\Result;
use RZP\Models\Payment\TwoFaStatus;

class ErrorCode
{
    /**
     * No Response From Visa Directory Server
     * Unable to Verify Enrollment
     * Invalid Response from Directory Server
     * Authentication Not Available
     * Invalid Payer Authentication Response
     *
     * This code can come for any of the above
     * scenarios.
     */
    const FSS0001   = 'FSS0001';

    const FSS00002  = 'FSS00002';

    const GW00150   = 'GW00150';
    const GW00151   = 'GW00151';
    const GW00152   = 'GW00152';
    const GW00153   = 'GW00153';
    const GW00154   = 'GW00154';

    const GW00157   = 'GW00157';

    const GW00159   = 'GW00159';
    const GW00160   = 'GW00160';
    const GW00161   = 'GW00161';
    const GW00162   = 'GW00162';
    const GW00163   = 'GW00163';
    const GW00164   = 'GW00164';
    const GW00165   = 'GW00165';
    const GW00166   = 'GW00166';
    const GW00167   = 'GW00167';

    const GW00170   = 'GW00170';
    const GW00171   = 'GW00171';

    const GW00176   = 'GW00176';
    const GW00177   = 'GW00177';

    const GW00181   = 'GW00181';

    const GW00183   = 'GW00183';

    const GW00201   = 'GW00201';
    const GW00205   = 'GW00205';
    const GW00258   = 'GW00258';
    const GW00259   = 'GW00259';
    const GW00261   = 'GW00261';

    const GW00456   = 'GW00456';
    const GW00458   = 'GW00458';
    const GW00850   = 'GW00850';
    const GW00856   = 'GW00856';

    /**
     * All codes in GV000** series
     * relate to errors in 3-d secure
     */

    /**
     * Unknown VPAS version
     * See bottom of below link to learn about VPAS.
     * We should not encounter it.
     * http://www.businessknowhow.com/money/ecombest.htm
     */
    const GV00001   = 'GV00001';

    const GV00002   = 'GV00002';
    const GV00003   = 'GV00003';
    const GV00004   = 'GV00004';
    const GV00005   = 'GV00005';
    const GV00006   = 'GV00006';
    const GV00007   = 'GV00007';
    const GV00008   = 'GV00008';
    const GV00009   = 'GV00009';
    const GV00010   = 'GV00010';
    const GV00011   = 'GV00011';
    const GV00012   = 'GV00012';
    const GV00013   = 'GV00013';

    const GV00100   = 'GV00100';
    const GV00101   = 'GV00101';
    const GV00102   = 'GV00102';
    const GV00103   = 'GV00103';
    const GV00104   = 'GV00104';

    const CM90000   = 'CM90000';
    const CM90001   = 'CM90001';
    const CM90002   = 'CM90002';
    const CM90003   = 'CM90003';
    const CM90004   = 'CM90004';
    const CM90005   = 'CM90005';

    const PY20001   = 'PY20001';
    const PY20002   = 'PY20002';
    const PY20006   = 'PY20006';
    const PY20085   = 'PY20085';

    //
    // The error codes starting with 'RP' are our custom ones
    // to handle different error cases not covered by their
    // the gateway defined error codes.
    //

    /**
     * Invalid error code.
     * Set it whenever encountering an unknown error code
     */
    const RP00001   = 'RP00001';

    /**
     * When for the given request, the returned 'result' code
     * isn't recognized
     */
    const RP00002   = 'RP00002';

    /**
     * Gateway request timeout
     */
    const RP00003   = 'RP00003';

    /**
     * When response result is 'HOST TIMEOUT'
     */
    const RP00004   = 'RP00004';

    /**
     * When response result is 'DENIED BY RISK'
     */
    const RP00005   = 'RP00005';

    /**
     * When response result is 'NOT APPROVED'
     */
    const RP00006   = 'RP00006';

    /**
     * When response result is 'NOT CAPTURED'
     */
    const RP00007   = 'RP00007';

    /**
     * When response status_code is greater than 500
     * signifying gateway server error
     */
    const RP00008   = 'RP00008';

    /**
     * When response content-type is not
     * application/xml
     */
    const RP00009   = 'RP00009';

    /**
     * When enroll response result code is
     * AUTH ERROR
     */
    const RP00010   = 'RP00010';

    /**
     * When enroll response result code is
     * CANCELLED
     */
    const RP00011   = 'RP00011';

    /**
     * When enroll response result code is
     * NOT SUPPORTED
     */
    const RP00012   = 'RP00012';

    public static $resultToErrorCodeMap = array(
        Result::HOST_TIMEOUT        => self::RP00004,
        Result::DENIED_BY_RISK      => self::RP00005,
        Result::NOT_APPROVED        => self::RP00006,
        Result::NOT_CAPTURED        => self::RP00007,
        Result::AUTH_ERROR          => self::RP00010,
        Result::CANCELED            => self::RP00011,
        Result::NOT_SUPPORTED       => self::RP00012,
    );

    public static $errorMessages = array(
        Hdfc\ErrorCode::FSS0001   => 'Authentication Not Available',
        Hdfc\ErrorCode::FSS00002  => 'Duplicate Payment Request',

        Hdfc\ErrorCode::GW00150   => 'Missing required data',
        Hdfc\ErrorCode::GW00151   => 'Invalid action type. Card network not supported',
        Hdfc\ErrorCode::GW00152   => 'Invalid Payment Amount',
        Hdfc\ErrorCode::GW00153   => 'Invalid Payment ID',
        Hdfc\ErrorCode::GW00154   => 'Invalid Terminal ID',

        Hdfc\ErrorCode::GW00157   => 'Invalid Payment Instrument',

        Hdfc\ErrorCode::GW00159   => 'Card number missing',
        Hdfc\ErrorCode::GW00160   => 'Invalid Brand',
        Hdfc\ErrorCode::GW00161   => 'Invalid Card/Member Name data',
        Hdfc\ErrorCode::GW00162   => 'Invalid User Defined data',
        Hdfc\ErrorCode::GW00163   => 'Invalid Address data',
        Hdfc\ErrorCode::GW00164   => 'Invalid Zip Code data',
        Hdfc\ErrorCode::GW00165   => 'Invalid Track ID data',
        Hdfc\ErrorCode::GW00166   => 'Invalid Card Number data',
        Hdfc\ErrorCode::GW00167   => 'Invalid Currency Code data',

        Hdfc\ErrorCode::GW00170   => 'Terminal ID Mismatch',

        Hdfc\ErrorCode::GW00176   => 'Failed Previous Captures check.',
        Hdfc\ErrorCode::GW00177   => 'Failed Capture Greater Than Auth check',

        Hdfc\ErrorCode::GW00181   => 'Failed Credit Greater Than Debit check',

        Hdfc\ErrorCode::GW00183   => 'Card Verification Digit Required',

        Hdfc\ErrorCode::GW00201   => 'Transaction not found',
        Hdfc\ErrorCode::GW00205   => 'Invalid Subsequent Payment',
        Hdfc\ErrorCode::GW00258   => 'Payment Denied: Negative BIN',
        Hdfc\ErrorCode::GW00259   => 'Payment Denied: Declined Card',
        Hdfc\ErrorCode::GW00261   => 'Payment Denied: Captures exceed Authorizations',

        Hdfc\ErrorCode::GW00456   => 'Invalid TranPortal Id',
        Hdfc\ErrorCode::GW00458   => 'Invalid Payment Attempt',
        Hdfc\ErrorCode::GW00850   => 'Missing Required data',
        Hdfc\ErrorCode::GW00856   => 'Invalid cvv',

        Hdfc\ErrorCode::GV00001   => 'Unknown VPAS version',
        Hdfc\ErrorCode::GV00002   => 'Cardholder not enrolled',
        Hdfc\ErrorCode::GV00003   => 'Not a VPAS Card',
        Hdfc\ErrorCode::GV00004   => 'PARes status not sucessful',
        Hdfc\ErrorCode::GV00005   => 'Certificate chain validation failed',
        Hdfc\ErrorCode::GV00006   => 'Certificate chain validation error',
        Hdfc\ErrorCode::GV00007   => 'Signature Validation failed',
        Hdfc\ErrorCode::GV00008   => 'Signature Validation error',
        Hdfc\ErrorCode::GV00009   => 'Invalid root certificate',
        Hdfc\ErrorCode::GV00010   => 'Missing data type',
        Hdfc\ErrorCode::GV00011   => 'Invalid expiration date',
        Hdfc\ErrorCode::GV00012   => 'Invalid action type',
        Hdfc\ErrorCode::GV00013   => 'Invalid Payment ID',

        Hdfc\ErrorCode::GV00100   => 'Invalid action type',
        Hdfc\ErrorCode::GV00101   => 'Missing data type',
        Hdfc\ErrorCode::GV00102   => 'Invalid Amount',
        Hdfc\ErrorCode::GV00103   => 'Invalid Brand',
        Hdfc\ErrorCode::GV00104   => 'Payment ID not numeric',

        Hdfc\ErrorCode::PY20006   => 'Invalid Brand',
        Hdfc\ErrorCode::PY20001   => 'Invalid Action Type',
        Hdfc\ErrorCode::PY20002   => 'Invalid amount',
        Hdfc\ErrorCode::PY20085   => 'Payment failed',

        Hdfc\ErrorCode::CM90000   => 'Database error',
        Hdfc\ErrorCode::CM90001   => 'Database configuration error',
        Hdfc\ErrorCode::CM90002   => 'Data format error',
        Hdfc\ErrorCode::CM90003   => 'No records found',
        Hdfc\ErrorCode::CM90004   => 'Duplicate records found',
        Hdfc\ErrorCode::CM90005   => 'Timestamp mismatch error',

        Hdfc\ErrorCode::RP00001   => 'Invalid Error Code. The error code returned is not recognized',
        Hdfc\ErrorCode::RP00002   => 'Invalid Result Code. The result code returned is not recognized',
        Hdfc\ErrorCode::RP00003   => 'Gateway request timeout. Request actually timed out with no result returned.',
        Hdfc\ErrorCode::RP00004   => 'Gateway request timeout. Response returned but response result code is "HOST TIMEOUT"',
        Hdfc\ErrorCode::RP00005   => 'Denied by risk. Response result code is "DENIED BY RISK"',
        Hdfc\ErrorCode::RP00006   => 'Authorization not approved. Response result code is "NOT APPROVED"',
        Hdfc\ErrorCode::RP00007   => 'Purchase/Capture/Refund not done. Response result code is "NOT CAPTURED"',
        Hdfc\ErrorCode::RP00008   => 'Gateway server error. Wrong response http status_code, > than 500 signifying gateway server error',
        Hdfc\ErrorCode::RP00009   => 'Wrong resposne content-type, not application/xml',
        Hdfc\ErrorCode::RP00010   => 'Result code is AUTH ERROR. This happens mostly because card number provided is invalid',
        Hdfc\ErrorCode::RP00011   => 'Result Code is CANCELED. This happens mostly when user cancels the payment on rupay 3dsecure page.',
        Hdfc\ErrorCode::RP00012   => 'Enroll result code is NOT SUPPORTED. This happens most probably when card network is not supported',
    );

    /**
     * Maps error codes from HDFC Gateway to the
     * app's gateway agnostic codes
     * @var array
     */
    public static $errorMap = array(
        Hdfc\ErrorCode::FSS0001   => Error\ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
        Hdfc\ErrorCode::FSS00002  => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,

        Hdfc\ErrorCode::GW00150   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        Hdfc\ErrorCode::GW00151   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
        Hdfc\ErrorCode::GW00152   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        Hdfc\ErrorCode::GW00153   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        Hdfc\ErrorCode::GW00154   => Error\ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,

        Hdfc\ErrorCode::GW00157   => Error\ErrorCode::GATEWAY_ERROR_NOT_UNDERSTOOD_ERROR,
        Hdfc\ErrorCode::GW00160   => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND,
        Hdfc\ErrorCode::GW00161   => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_NAME,
        Hdfc\ErrorCode::GW00162   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_UDF,
        Hdfc\ErrorCode::GW00163   => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        Hdfc\ErrorCode::GW00164   => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_ZIP,
        Hdfc\ErrorCode::GW00165   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        Hdfc\ErrorCode::GW00166   => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER,
        Hdfc\ErrorCode::GW00167   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        Hdfc\ErrorCode::GW00170   => Error\ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        Hdfc\ErrorCode::GW00176   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED,
        Hdfc\ErrorCode::GW00177   => Error\ErrorCode::GATEWAY_ERROR_CAPTURE_GREATER_THAN_AUTH,
        Hdfc\ErrorCode::GW00181   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        Hdfc\ErrorCode::GW00183   => Error\ErrorCode::GATEWAY_ERROR_CARD_MISSING_CVV,
        Hdfc\ErrorCode::GW00458   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        Hdfc\ErrorCode::GW00850   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        Hdfc\ErrorCode::GW00201   => Error\ErrorCode::GATEWAY_ERROR_SUPPORT_AUTH_NOT_FOUND,
        Hdfc\ErrorCode::GW00205   => Error\ErrorCode::GATEWAY_ERROR_INVALID_SUBSEQUENT_PAYMENT,

        Hdfc\ErrorCode::GW00258   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_DENIED_NEGATIVE_BIN,
        Hdfc\ErrorCode::GW00259   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,

        Hdfc\ErrorCode::GW00456   => Error\ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        Hdfc\ErrorCode::GW00856   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,

        Hdfc\ErrorCode::GV00004   => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL,
        Hdfc\ErrorCode::GV00005   => Error\ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        Hdfc\ErrorCode::GV00006   => Error\ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        Hdfc\ErrorCode::GV00007   => Error\ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        Hdfc\ErrorCode::GV00008   => Error\ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        Hdfc\ErrorCode::GV00011   => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_EXPIRY_DATE,

        Hdfc\ErrorCode::GV00100   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        Hdfc\ErrorCode::GV00103   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID,

        Hdfc\ErrorCode::PY20006   => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND,
        Hdfc\ErrorCode::PY20001   => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        Hdfc\ErrorCode::PY20002   => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_AMOUNT,
        Hdfc\ErrorCode::PY20085   => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,

        Hdfc\ErrorCode::CM90000   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        Hdfc\ErrorCode::CM90001   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        Hdfc\ErrorCode::CM90002   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        Hdfc\ErrorCode::CM90003   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        Hdfc\ErrorCode::CM90004   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        Hdfc\ErrorCode::CM90005   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        Hdfc\ErrorCode::RP00001   => Error\ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        Hdfc\ErrorCode::RP00002   => Error\ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        Hdfc\ErrorCode::RP00003   => Error\ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        Hdfc\ErrorCode::RP00004   => Error\ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        Hdfc\ErrorCode::RP00005   => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        Hdfc\ErrorCode::RP00006   => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        Hdfc\ErrorCode::RP00007   => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        Hdfc\ErrorCode::RP00008   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        Hdfc\ErrorCode::RP00009   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        Hdfc\ErrorCode::RP00010   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        Hdfc\ErrorCode::RP00011   => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL,
        Hdfc\ErrorCode::RP00012   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
    );

    public static $invalidErrorCode = Hdfc\ErrorCode::RP00001;

    public static $invalidResultErrorCode = self::RP00002;

    public static function getErrorCodeForResult($result)
    {
        if (isset(self::$resultToErrorCodeMap[$result]))
        {
            return self::$resultToErrorCodeMap[$result];
        }

        return self::$invalidResultErrorCode;
    }

    public static function getTwoFaStatus($code)
    {
        switch ($code) {
            case 'GV00004':
            case 'GV00007':
            case 'GV00008':
                return TwoFaStatus::FAILED;
            default:
                return TwoFaStatus::UNKNOWN;
        }
    }
}
