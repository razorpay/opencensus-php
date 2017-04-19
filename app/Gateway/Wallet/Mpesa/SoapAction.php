<?php

namespace RZP\Gateway\Wallet\Mpesa;

class SoapAction
{
    const QUERY_API        = "<pay: queryPaymentTransaction />";
    const CUSTOMER_API     = "<pay: validateCustomer />";
    const OTP_GENERATE_API = "<pay: pgSendOTP />";
    const REFUND_API       = "<pay: refundPaymentTransaction />";
    const OTP_SUBMIT_API   = "<pay: pgMrchntPymt />";
}
