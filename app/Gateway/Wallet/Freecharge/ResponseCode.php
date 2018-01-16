<?php

namespace RZP\Gateway\Wallet\Freecharge;

use RZP\Gateway\Base;

class ResponseCode extends Base\ResponseCode
{
    const SUCCESS_CODE = 'E000';

    public static $codes = [
        'E001'      => 'Invalid merchant Id',
        'E002'      => 'Merchant not allowed for transaction',
        'E003'      => 'Invalid merchant transaction id',
        'E004'      => 'Duplicate merchant transaction id',
        'E005'      => 'Invalid checksum/checksum mismatch',
        'E006'      => 'Invalid request IP',
        'E007'      => 'Request data contains invalid characters',
        'E008'      => 'No transaction found for given txnId or merchantTxnId',
        'E018'      => 'Application error occurred',
        'E023'      => 'Invalid channel',
        'E024'      => 'Amount precision should be max 2 digit',
        'E026'      => 'Either txnId OR merchantTxnId should be present in request',
        'E027'      => 'refundMerchantTxnId should not be blank',
        'E100'      => 'Error processing request',
        'E102'      => 'Invalid payment type code',
        'E103'      => 'Invalid PG',
        'E104'      => 'Amount not parsable',
        'E105'      => 'Negative amount',
        'E108'      => 'User entered wrong invalid card number',
        'E109'      => 'User entered wrong CVV number',
        'E113'      => 'Empty/Invalid Bank Code',
        'E114'      => 'Invalid Bank Code for specified PG',
        'E121'      => 'Merchant transaction id longer than allowed length',
        'E300'      => 'Invalid refund amount',
        'E301'      => 'Refund processing failure',
        'E600'      => 'Empty Merchant Identity',
        'E601'      => 'Empty Merchant Transaction Entity',
        'E603'      => 'Empty Failure URL',
        'E604'      => 'Empty Success URL',
        'E616'      => 'OAuth not enabled for merchant',
        'E617'      => 'Auth code is blank',
        'E618'      => 'access token is blank',
        'E619'      => 'Invalid auth code',
        'E620'      => 'Invalid access token',
        'E621'      => 'Invalid grant type',
        'E622'      => 'Refresh token is blank',
        'E623'      => 'Grant type is blank',
        'E624'      => 'Refresh token is invalid',
        'E625'      => 'Amount can not be greater than wallet balance',
        'E626'      => 'Customer payment is not in success state, refund is not possible',
        'E627'      => 'Same idempotency Id For Different Refund Request',
        'E628'      => 'Transaction amount must be greater than 1',
        'E629'      => 'Refund Amount Exceeding Transaction Amount',
        'E701'      => 'Invalid OTP id',
        'E702'      => 'Invalid OTP',
        'E705'      => 'CallbackUrl is not registered',
        'E867'      => 'Your account is suspended by freecharge',
        // UI Status Codes
        'EU001'     => 'Issue occurred while processing. Please contact customer support.',
        'EU002'     => 'User does not exist',
        'EU004'     => 'An account already exists with this email or mobile number',
        'EU005'     => 'Your account is locked. Please try after few minutes.',
        'EU006'     => 'Please upgrade your account to Freecharge Wallet first',
        'EU007'     => 'Request has been tampered',
        'EU008'     => 'This email is already registered with other user; please enter a different email address.',
        'EU009'     => 'Mobile number is already associated with other account',
        'EU010'     => 'Please enter correct OTP, or use Resend Code',
        'EU11'      => 'Incorrect username or password',
        'EU12'      => 'You have exceeded the maximum limit of OTPs. Please try after few minutes.',
        'EU13'      => 'OTP Expired',
    ];

    public static function isStatusUnknownError(string $errorCode = null)
    {
        return $errorCode === 'E018';
    }

    public static function isTransactionAbsent(string $errorCode)
    {
        return $errorCode === 'E008';
    }
}
