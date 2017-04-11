<?php

namespace RZP\Gateway\Wallet\Mpesa;

class SoapMethod
{
    // Soap Methods
    const QUERY_PAYMENT_TRANSACTION = 'queryPaymentTransaction';
    const VALIDATE_CUSTOMER         = 'validateCustomer';
    const SEND_OTP                  = 'pgSendOTP';
    const REFUND_PAYMENT            = 'refundPaymentTransaction';
    const OTP_SUBMIT                = 'pgMrchntPymt';
}
