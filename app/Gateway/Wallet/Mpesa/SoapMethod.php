<?php

namespace RZP\Gateway\Wallet\Mpesa;

class SoapMethod
{
    // Soap Methods
    const VALIDATE_CUSTOMER         = 'validateCustomer';
    const SEND_OTP                  = 'pgSendOTP';
    const OTP_SUBMIT                = 'pgMrchntPymt';
    const QUERY_PAYMENT_TRANSACTION = 'queryPaymentTransaction';
    const REFUND_PAYMENT            = 'refundPaymentTransaction';

    const METHOD_TO_ERROR_MESSAGE_MAP = [
        self::VALIDATE_CUSTOMER         => 'Validate customer failed',
        self::SEND_OTP                  => 'Send otp failed',
        self::OTP_SUBMIT                => 'Otp submit failed',
        self::QUERY_PAYMENT_TRANSACTION => 'Verify failed',
        self::REFUND_PAYMENT            => 'Refund failed',
    ];

    const DEFAULT_ERROR_MESSAGE = 'Random soap fault';

    public static function getErrorMessage($method)
    {
        return (self::METHOD_TO_ERROR_MESSAGE_MAP[$method] ??
               self::DEFAULT_ERROR_MESSAGE);
    }
}
