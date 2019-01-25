<?php

namespace RZP\Gateway\Upi\Axis;

class Url
{
    const TEST_DOMAIN       = 'https://upiuat.axisbank.co.in';

    const LIVE_DOMAIN       = 'https://pingupi.axisbank.co.in';

    const AUTHORIZE         = '/WebPaymentS2S/Merchant/requestCollect/';

    const FETCH_TOKEN       = '/WebPaymentS2S/Merchant/MerchantToken';

    const FETCH_TOKEN_TPV   = '/WebPaymentS2S/Merchant/MerchantTokenEncryption';

    const VERIFY            = '/WebPaymentS2S/Merchant/checkstatusV3';

    const REFUND            = '/WebPaymentS2S/Merchant/refund';

    const PAY               = '/WebPaymentS2S/Merchant/MerchRefid';
}
