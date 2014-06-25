<?php

namespace Gateway\HdfcGateway;

use \EE\Error\ErrorCode;

class HdfcGatewayErrorCode
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

    const GW00177   = 'GW00177';

    const GW00181   = 'GW00181';

    const GW00183   = 'GW00183';

    const GW00201   = 'GW00201';
    const GW00205   = 'GW00205';
    const GW00258   = 'GW00258';
    const GW00259   = 'GW00259';
    const GW00261   = 'GW00261';

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

    const PY20001   = 'PY20001';
    const PY20002   = 'PY20002';
    const PY20006   = 'PY20006';

    const RP00001   = 'RP00001';
    const RP00002   = 'RP00002';
    const RP00003   = 'RP00003';
    const RP00004   = 'RP00004';

    //
    // The error codes starting with 'RP' are our custom ones
    // to handle unknow error cases returned from bank.
    //

    public static $errorMessages = array(
        HdfcGatewayErrorCode::FSS0001   => 'Authentication Not Available',
        HdfcGatewayErrorCode::FSS00002  => 'Duplicate Transaction Request',

        HdfcGatewayErrorCode::GW00150   => 'Missing required data',
        HdfcGatewayErrorCode::GW00151   => 'Invalid action type',
        HdfcGatewayErrorCode::GW00152   => 'Invalid Transaction Amount',
        HdfcGatewayErrorCode::GW00153   => 'Invalid Transaction ID',
        HdfcGatewayErrorCode::GW00154   => 'Invalid Terminal ID',

        HdfcGatewayErrorCode::GW00157   => 'Invalid Payment Instrument',

        HdfcGatewayErrorCode::GW00159   => 'Card number missing',
        HdfcGatewayErrorCode::GW00160   => 'Invalid Brand.',
        HdfcGatewayErrorCode::GW00161   => 'Invalid Card/Member Name data',
        HdfcGatewayErrorCode::GW00162   => 'Invalid User Defined data',
        HdfcGatewayErrorCode::GW00163   => 'Invalid Address data',
        HdfcGatewayErrorCode::GW00164   => 'Invalid Zip Code data',
        HdfcGatewayErrorCode::GW00165   => 'Invalid Track ID data',
        HdfcGatewayErrorCode::GW00166   => 'Invalid Card Number data',
        HdfcGatewayErrorCode::GW00167   => 'Invalid Currency Code data',

        HdfcGatewayErrorCode::GW00170   => 'Terminal ID Mismatch',

        HdfcGatewayErrorCode::GW00177   => 'Failed Support Greater Than Auth check',

        HdfcGatewayErrorCode::GW00181   => 'Failed Credit Greater Than Debit check',

        HdfcGatewayErrorCode::GW00183   => 'Card Verification Digit Required',

        HdfcGatewayErrorCode::GW00201   => 'Support Error Auth not found',
        HdfcGatewayErrorCode::GW00205   => 'Invalid Subsequent Transaction',
        HdfcGatewayErrorCode::GW00258   => 'Transaction Denied: Negative BIN',
        HdfcGatewayErrorCode::GW00259   => 'Transaction Denied: Declined Card',
        HdfcGatewayErrorCode::GW00261   => 'Transaction Denied: Captures exceed Authorizations',

        HdfcGatewayErrorCode::GV00001   => 'Unknown VPAS version',
        HdfcGatewayErrorCode::GV00002   => 'Cardholder not enrolled',
        HdfcGatewayErrorCode::GV00003   => 'Not a VPAS Card',
        HdfcGatewayErrorCode::GV00004   => 'PARes status not sucessful',
        HdfcGatewayErrorCode::GV00005   => 'Certificate chain validation failed',
        HdfcGatewayErrorCode::GV00006   => 'Certificate chain validation error',
        HdfcGatewayErrorCode::GV00007   => 'Signature Validation failed',
        HdfcGatewayErrorCode::GV00008   => 'Signature Validation error',
        HdfcGatewayErrorCode::GV00009   => 'Invalid root certificate',
        HdfcGatewayErrorCode::GV00010   => 'Missing data type',
        HdfcGatewayErrorCode::GV00011   => 'Invalid expiration date',
        HdfcGatewayErrorCode::GV00012   => 'Invalid action type',
        HdfcGatewayErrorCode::GV00013   => 'Invalid Payment ID',

        HdfcGatewayErrorCode::GV00100   => 'Invalid action type',
        HdfcGatewayErrorCode::GV00101   => 'Missing data type',
        HdfcGatewayErrorCode::GV00102   => 'Invalid Amount',
        HdfcGatewayErrorCode::GV00103   => 'Invalid Brand',
        HdfcGatewayErrorCode::GV00104   => 'Payment ID not numeric',

        HdfcGatewayErrorCode::PY20006   => 'Invalid Brand',
        HdfcGatewayErrorCode::PY20001   => 'Invalid Action Type',
        HdfcGatewayErrorCode::PY20002   => 'Invalid amount',

        HdfcGatewayErrorCode::RP00001   => 'Invalid Error Code',
        HdfcGatewayErrorCode::RP00002   => 'Uncaptured Transaction',
        HdfcGatewayErrorCode::RP00003   => 'Invalid enroll code',
        HdfcGatewayErrorCode::RP00004   => 'Gateway request timeout');

    /**
     * Maps error codes from HDFC Gateway to the
     * app's gateway agnostic codes
     * @var array
     */
    public static $errorMap = array(
        HdfcGatewayErrorCode::FSS0001   => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
        HdfcGatewayErrorCode::FSS00002  => ErrorCode::GATEWAY_ERROR_TRANSACTION_DUPLICATE_REQUEST,

        HdfcGatewayErrorCode::GW00150   => ErrorCode::GATEWAY_ERROR_TRANSACTION_MISSING_DATA,
        HdfcGatewayErrorCode::GW00151   => ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_ACTION,
        HdfcGatewayErrorCode::GW00152   => ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_AMOUNT,
        HdfcGatewayErrorCode::GW00153   => ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_ID,
        HdfcGatewayErrorCode::GW00154   => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,

        HdfcGatewayErrorCode::GW00157   => ErrorCode::GATEWAY_ERROR_NOT_UNDERSTOOD_ERROR,
        HdfcGatewayErrorCode::GW00160   => ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND,
        HdfcGatewayErrorCode::GW00161   => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NAME,
        HdfcGatewayErrorCode::GW00162   => ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_UDF,
        HdfcGatewayErrorCode::GW00163   => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        HdfcGatewayErrorCode::GW00164   => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ZIP,
        HdfcGatewayErrorCode::GW00165   => ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_ID,
        HdfcGatewayErrorCode::GW00166   => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER,
        HdfcGatewayErrorCode::GW00167   => ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_CURRENCY,
        HdfcGatewayErrorCode::GW00170   => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        HdfcGatewayErrorCode::GW00177   => ErrorCode::GATEWAY_ERROR_SUPPORT_LESS_THAN_AUTH,
        HdfcGatewayErrorCode::GW00181   => ErrorCode::CARD_ERROR_INSUFFICIENT_BALANCE,
        HdfcGatewayErrorCode::GW00183   => ErrorCode::GATEWAY_ERROR_CARD_MISSING_CVV,

        HdfcGatewayErrorCode::GW00201   => ErrorCode::GATEWAY_ERROR_SUPPORT_AUTH_NOT_FOUND,
        HdfcGatewayErrorCode::GW00205   => ErrorCode::GATEWAY_ERROR_INVALID_SUBSEQUENT_TRANSACTION,

        HdfcGatewayErrorCode::GW00258   => ErrorCode::GATEWAY_ERROR_TRANSACTION_DENIED_NEGATIVE_BIN,
        HdfcGatewayErrorCode::GW00259   => ErrorCode::CARD_ERROR_CARD_DECLINED,

        HdfcGatewayErrorCode::GV00004   => ErrorCode::GATEWAY_ERROR_PARES_NOT_SUCCESFUL,
        HdfcGatewayErrorCode::GV00005   => ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        HdfcGatewayErrorCode::GV00006   => ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        HdfcGatewayErrorCode::GV00007   => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        HdfcGatewayErrorCode::GV00008   => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        HdfcGatewayErrorCode::GV00011   => ErrorCode::GATEWAY_ERROR_CARD_INVALID_EXPIRY_DATE,

        HdfcGatewayErrorCode::PY20006   => ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND,
        HdfcGatewayErrorCode::PY20001   => ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_ACTION,
        HdfcGatewayErrorCode::PY20002   => ErrorCode::GATEWAY_ERROR_CARD_INVALID_AMOUNT,

        HdfcGatewayErrorCode::RP00001   => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR);

    public static $invalidErrorCode = HdfcGatewayErrorCode::RP00001;
}