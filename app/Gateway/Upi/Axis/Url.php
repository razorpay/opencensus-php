<?php

namespace RZP\Gateway\Upi\Axis;

class Url
{
    // TODO: Can /upi be moved to the end of this string
    const TEST_DOMAIN       = 'https://upiuat.axisbank.co.in';

    const AUTHORIZE         = '/WebPaymentS2S/Merchant/requestCollect/';

    const FETCH_TOKEN       = '/WebPaymentS2S/Merchant/MerchantToken';


    //    const VERIFY            = '/upi/transactionStatusQuery';
//    const REFUND            = '/upi/refundReqSvc';
//
//    const VALIDATE_VPA      = '/upi/checkMeVirtualAddress';
}
