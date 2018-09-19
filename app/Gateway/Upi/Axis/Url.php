<?php

namespace RZP\Gateway\Upi\Axis;

class Url
{
    const TEST_DOMAIN       = 'https://upiuat.axisbank.co.in';

    const AUTHORIZE         = '/WebPaymentS2S/Merchant/requestCollect/';

    const FETCH_TOKEN       = '/WebPaymentS2S/Merchant/MerchantToken';

    const VERIFY            = '/WebPaymentS2S/Merchant/checkstatusV3';

    const REFUND            = '/WebPaymentS2S/Merchant/txnRefund';
}
