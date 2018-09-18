<?php

namespace RZP\Gateway\Hdfc;

use RZP\Error;
use RZP\Gateway\Hdfc\Payment\Result;

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
    const GW00256   = 'GW00256';
    const GW00258   = 'GW00258';
    const GW00259   = 'GW00259';
    const GW00261   = 'GW00261';

    const GW00456   = 'GW00456';
    const GW00458   = 'GW00458';
    const GW00850   = 'GW00850';
    const GW00852   = 'GW00852';
    const GW00854   = 'GW00854';
    const GW00856   = 'GW00856';
    const GW00874   = 'GW00874';
    const GW00876   = 'GW00876';
    const GW02016   = 'GW02016';

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

    const CM00030   = 'CM00030';
    const CM90000   = 'CM90000';
    const CM90001   = 'CM90001';
    const CM90002   = 'CM90002';
    const CM90003   = 'CM90003';
    const CM90004   = 'CM90004';
    const CM90005   = 'CM90005';
    const CM900000  = 'CM900000';

    const PY20001   = 'PY20001';
    const PY20002   = 'PY20002';
    const PY20006   = 'PY20006';
    const PY20007   = 'PY20007';
    const PY20085   = 'PY20085';

    /**
     * New error codes after IPAY migration
     */
    const IPAY0200121 = 'IPAY0200121';

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
    const RP00015   = 'RP00015';

    /**
     * When response result is 'DENIED BY RISK'
     */
    const RP00005   = 'RP00005';

    /**
     * When response result is 'NOT APPROVED'
     */
    const RP00006   = 'RP00006';
    const RP00016   = 'RP00016';

    /**
     * When response result is 'NOT CAPTURED'
     */
    const RP00007   = 'RP00007';
    const RP00017   = 'RP00017';

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
    const RP00010     = 'RP00010';
    const RP00020     = 'RP00020';

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
    const RP00018   = 'RP00018';

    /**
     * Gateway request timeout
     * operation timed out specifically
     */
    const RP00013   = 'RP00013';

    /**
     * Any gateway request exception except request timeout.
     */
    const RP00014   = 'RP00014';

    /**
     * Transaction denied due to previous capture check failure
     * ( Validate Original Transaction )
     */

    const RP00021   = 'RP00021';

    public static $resultToErrorCodeMap = array(
        Result::HOST_TIMEOUT        => self::RP00004,
        Result::DENIED_BY_RISK      => self::RP00005,
        Result::NOT_APPROVED        => self::RP00006,
        Result::NOT_CAPTURED        => self::RP00007,
        Result::AUTH_ERROR          => self::RP00010,
        Result::CANCELED            => self::RP00011,
        Result::NOT_SUPPORTED       => self::RP00012,
        Result::HOST_TIMEOUT_IPAY   => self::RP00015,
        Result::NOT_APPROVED_IPAY   => self::RP00016,
        Result::NOT_CAPTURED_IPAY   => self::RP00017,
        Result::NOT_SUPPORTED_IPAY  => self::RP00018,
        Result::DENIED_BY_RISK_IPAY => self::GW00256,
        Result::AUTH_ERROR_IPAY     => self::RP00020,
        Result::DENIED_CAPTURE      => self::RP00021,
    );

    public static $errorMessages = array(
        self::FSS0001     => 'Authentication Not Available',
        self::FSS00002    => 'Duplicate Payment Request',

        self::GW00150     => 'Missing required data',
        self::GW00151     => 'Invalid action type. Card network not supported',
        self::GW00152     => 'Invalid Payment Amount',
        self::GW00153     => 'Invalid Payment ID',
        self::GW00154     => 'Invalid Terminal ID',

        self::GW00157     => 'Invalid Payment Instrument',

        self::GW00159     => 'Card number missing',
        self::GW00160     => 'Invalid Brand',
        self::GW00161     => 'Invalid Card/Member Name data',
        self::GW00162     => 'Invalid User Defined data',
        self::GW00163     => 'Invalid Address data',
        self::GW00164     => 'Invalid Zip Code data',
        self::GW00165     => 'Invalid Track ID data',
        self::GW00166     => 'Invalid Card Number data',
        self::GW00167     => 'Invalid Currency Code data',

        self::GW00170     => 'Terminal ID Mismatch',
        self::GW00171     => 'Payment Instrument Mismatch',

        self::GW00176     => 'Failed Previous Captures check.',
        self::GW00177     => 'Failed Capture Greater Than Auth check',

        self::GW00181     => 'Failed Credit Greater Than Debit check',

        self::GW00183     => 'Card Verification Digit Required',

        self::GW00201     => 'Transaction not found',
        self::GW00205     => 'Invalid Subsequent Payment',
        self::GW00256     => 'Denied by risk. Response result code is "DENIED BY RISK"',
        self::GW00258     => 'Payment Denied: Negative BIN',
        self::GW00259     => 'Payment Denied: Declined Card',
        self::GW00261     => 'Payment Denied: Captures exceed Authorizations',

        self::GW00456     => 'Invalid TranPortal Id',
        self::GW00458     => 'Invalid Payment Attempt',
        self::GW00850     => 'Missing Required data',
        self::GW00852     => 'Invalid card number',
        self::GW00854     => 'Invalid Expiration Date',
        self::GW00856     => 'Invalid cvv',
        self::GW00874     => 'Transaction denied due to expiration date.',
        self::GW00876     => 'Invalid cvv',
        self::GW02016     => 'Locale text not found',

        self::GV00001     => 'Unknown VPAS version',
        self::GV00002     => 'Cardholder not enrolled',
        self::GV00003     => 'Not a VPAS Card',
        self::GV00004     => 'PARes status not successful',
        self::GV00005     => 'Certificate chain validation failed',
        self::GV00006     => 'Certificate chain validation error',
        self::GV00007     => 'Signature Validation failed',
        self::GV00008     => 'Signature Validation error',
        self::GV00009     => 'Invalid root certificate',
        self::GV00010     => 'Missing data type',
        self::GV00011     => 'Invalid expiration date',
        self::GV00012     => 'Invalid action type',
        self::GV00013     => 'Invalid Payment ID',

        self::GV00100     => 'Invalid action type',
        self::GV00101     => 'Missing data type',
        self::GV00102     => 'Invalid Amount',
        self::GV00103     => 'Invalid Brand',
        self::GV00104     => 'Payment ID not numeric',

        self::PY20001     => 'Invalid Action Type',
        self::PY20002     => 'Invalid amount',
        self::PY20006     => 'Invalid Brand',
        self::PY20007     => 'Invalid Order status',
        self::PY20085     => 'Invalid payment status',

        self::CM00030     => '(HDFC internal error) Problem occured while getting external connection details',
        self::CM90000     => 'Database error',
        self::CM90001     => 'Database configuration error',
        self::CM90002     => 'Data format error',
        self::CM90003     => 'No records found',
        self::CM90004     => 'Duplicate records found',
        self::CM90005     => 'Timestamp mismatch error',
        self::CM900000    => '(HDFC internal error) Problem occurred while getting terminal.',

        self::RP00001     => 'Invalid Error Code. The error code returned is not recognized',
        self::RP00002     => 'Invalid Result Code. The result code returned is not recognized',
        self::RP00003     => 'Gateway request timeout. Request actually timed out with no result returned.',
        self::RP00004     => 'Gateway request timeout. Response returned but response result code is "HOST TIMEOUT"',
        self::RP00005     => 'Denied by risk. Response result code is "DENIED BY RISK"',
        self::RP00006     => 'Authentication not approved. Response result code is "NOT APPROVED"',
        self::RP00007     => 'Purchase/Capture/Refund not done. Response result code is "NOT CAPTURED"',
        self::RP00008     => 'Gateway server error. Wrong response http status_code, > than 500 signifying gateway server error',
        self::RP00009     => 'Wrong response content-type, not application/xml',
        self::RP00010     => 'Result code is AUTH ERROR. This happens mostly because card number provided is invalid',
        self::RP00011     => 'Result Code is CANCELED. This happens mostly when user cancels the payment on RuPay 3dsecure page.',
        self::RP00012     => 'Enroll result code is NOT SUPPORTED. This happens most probably when card network is not supported',
        self::RP00013     => 'Operation timed out while making the request',
        self::RP00014     => 'Gateway request failed due to some issue.',
        self::RP00015     => 'Gateway request timeout. Response returned but response result code is "HOST TIMEOUT"',
        self::RP00016     => 'Authorization not approved. Response result code is "NOT APPROVED"',
        self::RP00017     => 'Purchase/Capture/Refund not done. Response result code is "NOT CAPTURED"',
        self::RP00018     => 'Enroll result code is NOT SUPPORTED. This happens most probably when card network is not supported',

        self::RP00020     => 'Result code is AUTH ERROR. This happens mostly because card number provided is invalid',
        self::RP00021     => 'Transaction denied due to previous capture check failure ( Validate Original Transaction )',
        self::IPAY0200121 => 'FSSConnect Destination is down',
    );

    /**
     * Maps error codes from HDFC Gateway to the
     * app's gateway agnostic codes
     * @var array
     */
    public static $errorMap = array(
        self::FSS0001     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_NOT_AVAILABLE,
        self::FSS00002    => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,

        self::GW00150     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        self::GW00151     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
        self::GW00152     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::GW00153     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        self::GW00154     => Error\ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        self::GW00157     => Error\ErrorCode::GATEWAY_ERROR_NOT_UNDERSTOOD_ERROR,
        self::GW00159     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        self::GW00160     => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND,
        self::GW00161     => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_NAME,
        self::GW00162     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_UDF,
        self::GW00163     => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        self::GW00164     => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_ZIP,
        self::GV00104     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        self::GW00165     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        self::GW00166     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE,
        self::GW00167     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        self::GW00170     => Error\ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        self::GW00171     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GW00176     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED,
        self::GW00177     => Error\ErrorCode::GATEWAY_ERROR_CAPTURE_GREATER_THAN_AUTH,
        self::GW00181     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        self::GW00183     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_CVV_NOT_PROVIDED,
        self::GW00201     => Error\ErrorCode::GATEWAY_ERROR_SUPPORT_AUTH_NOT_FOUND,
        self::GW00205     => Error\ErrorCode::GATEWAY_ERROR_INVALID_SUBSEQUENT_PAYMENT,
        self::GW00256     => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        self::GW00258     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_DENIED_NEGATIVE_BIN,
        self::GW00259     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        self::GW00261     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GW00456     => Error\ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        self::GW00458     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GW00850     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GW00852     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        self::GW00854     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE,
        self::GW00856     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        self::GW00874     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE,
        self::GW00876     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        self::GW02016     => Error\ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,

        self::GV00001     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GV00002     => Error\ErrorCode::GATEWAY_ERROR_CARD_NOT_ENROLLED,
        self::GV00003     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GV00004     => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL,
        self::GV00005     => Error\ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        self::GV00006     => Error\ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        self::GV00007     => Error\ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::GV00008     => Error\ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::GV00009     => Error\ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        self::GV00010     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        self::GV00011     => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_EXPIRY_DATE,
        self::GV00012     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GV00013     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,

        self::GV00100     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        self::GV00101     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        self::GV00102     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::GV00103     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID,

        self::PY20001     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        self::PY20002     => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_AMOUNT,
        self::PY20006     => Error\ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND,
        self::PY20007     => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::PY20085     => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,

        self::CM00030     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90000     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90001     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90002     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90003     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90004     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90005     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM900000    => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::RP00001     => Error\ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        self::RP00002     => Error\ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        self::RP00003     => Error\ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        self::RP00004     => Error\ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        self::RP00005     => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        self::RP00006     => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        self::RP00007     => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        self::RP00008     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::RP00009     => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::RP00010     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        self::RP00011     => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL,
        self::RP00012     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
        self::RP00013     => Error\ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        self::RP00014     => Error\ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::RP00015     => Error\ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        self::RP00016     => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        self::RP00017     => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        self::RP00018     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,

        self::RP00020     => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        self::RP00021     => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED,
        self::IPAY0200121 => Error\ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
    );

    public static $invalidErrorCode = self::RP00001;

    public static $invalidResultErrorCode = self::RP00002;

    public static function getErrorCodeForResult($result)
    {
        if (isset(self::$resultToErrorCodeMap[$result]))
        {
            return self::$resultToErrorCodeMap[$result];
        }

        return self::$invalidResultErrorCode;
    }
}
