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
}
