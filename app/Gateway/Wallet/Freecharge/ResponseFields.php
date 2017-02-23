<?php

namespace RZP\Gateway\Wallet\Freecharge;

class ResponseFields
{
    const OTP_ID                 = 'otpId';
    const REDIRECT_URL           = 'redirectUrl';
    const STATUS                 = 'status';
    const IS_IVR_ENABLED         = 'isIvrEnabled';
    const ACCESS_TOKEN           = 'accessToken';
    const ACCESS_TOKEN_EXPIRY    = 'accessTokenExpiry';
    const REFRESH_TOKEN          = 'refreshToken';
    const REFRESH_TOKEN_EXPIRY   = 'refreshTokenExpiry';
    const AUTH_CODE              = 'authCode';
    const WALLET_BALANCE         = 'walletBalance';
    const TXN_ID                 = 'txnId';
    const MERCHANT_TXN_ID        = 'merchantTxnId';
    const AMOUNT                 = 'amount';
    const CHECKSUM               = 'checksum';
    const ERROR_CODE             = 'errorCode';
    const METADATA               = 'metadata';
    const ERROR_MESSAGE          = 'errorMessage';
    const REFUND_TXN_ID          = 'refundTxnId';
    const REFUNDED_AMOUNT        = 'refundedAmount';
    const REFUND_MERCHANT_TXN_ID = 'refundMerchantTxnId';
}
