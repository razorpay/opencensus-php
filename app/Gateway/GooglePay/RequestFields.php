<?php

namespace RZP\Gateway\GooglePay;

class RequestFields
{
    const PAYMENT_ID        = 'pgTransactionRefId';

    const CARD_TYPE         = 'cardType';

    const CARD_NETWORK      = 'network';

    const AMOUNT            = 'amount';

    const TOKEN             = 'token';

    const PG_BUNDLE         = 'pgBundle';

    const METHOD_DETAILS    = 'paymentMethodDetails';

    const CARD_NUMBER       = 'dpan';

    const CARD_EXPIRY_MONTH = 'expirationMonth';

    const CARD_EXPIRT_YEAR  = 'expirationYear';

    const MERCHANT_ID       = 'gatewayMerchantId';

    const CRYPTOGRAM_3DS    = '3dsCryptogram';

    const ECI_INDICATOR_3DS = '3dsEciIndicator';
}
