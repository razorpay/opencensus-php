<?php

namespace RZP\Gateway\Wallet\Mpesa;

class RequestFields
{
    // POST params
    const GATEWAY_PARAM           = 'gatewayparam';
    const CHECKSUM                = 'checksum';

    // XML params
    const PAYMENT_GATEWAY_REQUEST = 'PaymentGatewayRequest';
    const MERCHANT_CODE           = 'MCODE';
    const TRANSACTION_DATE        = 'TXNDATE';
    const TRANSACTION_TYPE        = 'TXNTYPE';
    const TRANSACTION_REFERENCE   = 'TRANSREFNO';
    const AMOUNT                  = 'AMT';
    const NARRATION               = 'NARRATION';
    const RETURN_URL              = 'RETURNURL';
    const SURCHARGE               = 'SURCHARGE';
}
