<?php

namespace Gateway\HdfcGateway;

class HdfcGatewayErrorCode
{
    const FSS0001   = 'FSS0001';
    const FSS00002  = 'FSS00002';
    const GW00150   = 'GW00150';
    const GW00151   = 'GW00151';
    const GW00152   = 'GW00152';
    const GW00153   = 'GW00153';
    const GW00154   = 'GW00154';
    const GW00159   = 'GW00159';
    const GW00181   = 'GW00181';
    const GW00205   = 'GW00205';
    const GW00157   = 'GW00157';
    const GW00165   = 'GW00165';
    const GW00166   = 'GW00166';
    const GW00167   = 'GW00167';
    const GW00170   = 'GW00170';
    const GW00171   = 'GW00171';
    const GW00160   = 'GW00160';
    const GW00161   = 'GW00161';
    const GW00162   = 'GW00162';
    const GW00163   = 'GW00163';
    const GW00164   = 'GW00164';
    const GW00177   = 'GW00177';
    const GW00183   = 'GW00183';
    const GW00201   = 'GW00201';
    const GW00258   = 'GW00258';
    const GW00259   = 'GW00259';
    const GV00004   = 'GV00004';
    const GV00005   = 'GV00005';
    const GV00006   = 'GV00006';
    const GV00007   = 'GV00007';
    const GV00008   = 'GV00008';
    const GV00011   = 'GV00011';
    const PY20006   = 'PY20006';
    const PY20001   = 'PY20001';
    const PY20002   = 'PY20002';
    const RP00001   = 'RP00001';
    const RP00002   = 'RP00002';
    const RP00003   = 'RP00003';

    // The error codes starting with 'RP' are our custom ones
    // to handle unknow error cases returned from bank.

    public static $errorMessages = array(
        HdfcGatewayErrorCode::FSS0001   => 'Authentication Not Available',
        HdfcGatewayErrorCode::FSS00002  => 'Duplicate Transaction Request',
        HdfcGatewayErrorCode::GW00150   => 'Missing Required Data',
        HdfcGatewayErrorCode::GW00151   => 'Invalid Action type',
        HdfcGatewayErrorCode::GW00152   => 'Invalid Transaction Amount',
        HdfcGatewayErrorCode::GW00153   => 'Invalid Transaction ID',
        HdfcGatewayErrorCode::GW00154   => 'Invalid Terminal ID',
        HdfcGatewayErrorCode::GW00159   => 'Card Number Missing',
        HdfcGatewayErrorCode::GW00181   => 'Failed Credit Greater Than Debit check',
        HdfcGatewayErrorCode::GW00205   => 'Invalid Subsequent Transaction',
        HdfcGatewayErrorCode::GW00157   => 'Invalid Payment Instrument',
        HdfcGatewayErrorCode::GW00165   => 'Invalid Track ID data',
        HdfcGatewayErrorCode::GW00166   => 'Invalid Card Number data',
        HdfcGatewayErrorCode::GW00167   => 'Invalid Currency Code data',
        HdfcGatewayErrorCode::GW00170   => 'Terminal ID Mismatch',
        HdfcGatewayErrorCode::GW00171   => 'Payment Instrument Mismatch',
        HdfcGatewayErrorCode::GW00160   => 'Invalid Brand.',
        HdfcGatewayErrorCode::GW00161   => 'Invalid Card/Member Name data',
        HdfcGatewayErrorCode::GW00162   => 'Invalid User Defined data',
        HdfcGatewayErrorCode::GW00163   => 'Invalid Address data',
        HdfcGatewayErrorCode::GW00164   => 'Invalid Zip Code data',
        HdfcGatewayErrorCode::GW00177   => 'Failed Support Greater Than Auth check',
        HdfcGatewayErrorCode::GW00183   => 'Card Verification Digit Required',
        HdfcGatewayErrorCode::GW00201   => 'Support Error Auth not found',
        HdfcGatewayErrorCode::GW00258   => 'Transaction Denied: Negative BIN',
        HdfcGatewayErrorCode::GW00259   => 'Transaction Denied: Declined Card',
        HdfcGatewayErrorCode::GV00004   => 'PARes Status Not Sucessful',
        HdfcGatewayErrorCode::GV00005   => 'Certificate Chain Validation Failed',
        HdfcGatewayErrorCode::GV00006   => 'Certificate Chain Validation Error',
        HdfcGatewayErrorCode::GV00007   => 'Signature Validation Failed',
        HdfcGatewayErrorCode::GV00008   => 'Signature Validation Failed',
        HdfcGatewayErrorCode::GV00011   => 'Invalid Expiration Date',
        HdfcGatewayErrorCode::PY20006   => 'Invalid Brand',
        HdfcGatewayErrorCode::PY20001   => 'Invalid Action Type',
        HdfcGatewayErrorCode::PY20002   => 'Invalid amount',
        HdfcGatewayErrorCode::RP00001   => 'Invalid Error Code',
        HdfcGatewayErrorCode::RP00002   => 'Uncaptured Transaction',
        HdfcGatewayErrorCode::RP00003   => 'Invalid enroll code');

    /**
     * Maps error codes from HDFC Gateway to the
     * app's gateway agnostic codes
     * @var array
     */
    public static $errorMap = array(
        HdfcGatewayErrorCode::FSS0001   => Status::GATEWAY_AUTHENTICATION_NOT_AVAILABLE,
        HdfcGatewayErrorCode::FSS00002  => Status::GATEWAY_TRANSACTION_DUPLICATE_REQUEST,
        HdfcGatewayErrorCode::GW00150   => Status::GATEWAY_TRANSACTION_MISSING_DATA,
        HdfcGatewayErrorCode::GW00151   => Status::GATEWAY_TRANSACTION_INVALID_ACTION,
        HdfcGatewayErrorCode::GW00152   => Status::GATEWAY_CARD_INVALID_AMOUNT,
        HdfcGatewayErrorCode::GW00153   => Status::GATEWAY_TRANSACTION_INVALID_ID,
        HdfcGatewayErrorCode::GW00154   => Status::GATEWAY_INVALID_TERMINAL_ID,
        HdfcGatewayErrorCode::GW00181   => Status::GATEWAY_TRANSACTION_CREDIT_LESS_THAN_DEBIT,
        HdfcGatewayErrorCode::GW00205   => Status::GATEWAY_INVALID_SUBSEQUENT_TRANSACTION,
        HdfcGatewayErrorCode::GW00157   => Status::GATEWAY_NOT_UNDERSTOOD_ERROR,
        HdfcGatewayErrorCode::GW00165   => Status::GATEWAY_TRANSACTION_INVALID_ID,
        HdfcGatewayErrorCode::GW00166   => Status::GATEWAY_CARD_INVALID_NUMBER,
        HdfcGatewayErrorCode::GW00167   => Status::GATEWAY_TRANSACTION_INVALID_CURRENCY,
        HdfcGatewayErrorCode::GW00170   => Status::GATEWAY_GATEWAY_INVALID_TERMINAL_ID,
        HdfcGatewayErrorCode::GW00171   => Status::GATEWAY_NOT_UNDERSTOOD_ERROR,
        HdfcGatewayErrorCode::GW00160   => Status::GATEWAY_CARD_INVALID_BRAND,
        HdfcGatewayErrorCode::GW00161   => Status::GATEWAY_CARD_INVALID_NAME,
        HdfcGatewayErrorCode::GW00162   => Status::GATEWAY_TRANSACTION_INVALID_UDF,
        HdfcGatewayErrorCode::GW00163   => Status::GATEWAY_CARD_INVALID_ADDRESS,
        HdfcGatewayErrorCode::GW00164   => Status::GATEWAY_CARD_INVALID_ZIP,
        HdfcGatewayErrorCode::GW00183   => Status::GATEWAY_CARD_MISSING_CVC,
        HdfcGatewayErrorCode::GW00177   => Status::GATEWAY_SUPPORT_FAILED,
        HdfcGatewayErrorCode::GW00201   => Status::GATEWAY_SUPPORT_AUTH_NOT_FOUND,
        HdfcGatewayErrorCode::GW00258   => Status::GATEWAY_TRANSACTION_DENIED_NEGATIVE_BIN,
        HdfcGatewayErrorCode::GW00259   => Status::GATEWAY_CARD_DECLINED,
        HdfcGatewayErrorCode::GV00004   => Status::GATEWAY_PARES_NOT_SUCCESFUL,
        HdfcGatewayErrorCode::GV00005   => Status::GATEWAY_GATEWAY_CERTIFICATE_VALIDATION_FAILED,
        HdfcGatewayErrorCode::GV00006   => Status::GATEWAY_GATEWAY_CERTIFICATE_VALIDATION_FAILED,
        HdfcGatewayErrorCode::GV00007   => Status::GATEWAY_SIGNATURE_VALIDATION_FAILED,
        HdfcGatewayErrorCode::GV00008   => Status::GATEWAY_SIGNATURE_VALIDATION_FAILED,
        HdfcGatewayErrorCode::GV00011   => Status::GATEWAY_CARD_INVALID_EXPIRY_DATE,
        HdfcGatewayErrorCode::PY20006   => Status::GATEWAY_CARD_INVALID_BRAND,
        HdfcGatewayErrorCode::PY20001   => Status::GATEWAY_TRANSACTION_INVALID_ACTION,
        HdfcGatewayErrorCode::PY20002   => Status::GATEWAY_CARD_INVALID_AMOUNT,
        HdfcGatewayErrorCode::RP00001   => Status::GATEWAY_UNKNOWN_ERROR);

    public static $invalidErrorCode = HdfcGatewayErrorCode::RP00001;
}