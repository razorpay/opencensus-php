<?php

namespace RZP\Gateway\Upi\Axis;

class Url
{
    const TEST_DOMAIN       = 'https://upiuat.axisbank.co.in';

    const LIVE_DOMAIN       = 'https://pingupi.axisbank.co.in';

    const AUTHENTICATE      = '/WebPaymentS2S/Merchant/requestCollect/';

    const AUTHENTICATE_V2   = '/WebPaymentV2/Merchant/SingleCollectRequest';

    const FETCH_TOKEN       = '/WebPaymentS2S/Merchant/MerchantToken';

    const FETCH_TOKEN_TPV   = '/WebPaymentS2S/Merchant/MerchantTokenEncryption';

    const VERIFY            = '/WebPaymentS2S/Merchant/checkstatusV3';

    const VERIFY_REFUND     = '/Merchant/OfflineRefund/status';

    const REFUND            = '/WebPaymentS2S/Merchant/refund';

    const PAY               = '/WebPaymentS2S/Merchant/MerchRefid';

    const PAY_V2            = '/WebPaymentS2S/Merchant/MerchRefid2';
}
