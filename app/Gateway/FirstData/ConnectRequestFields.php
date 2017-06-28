<?php

namespace RZP\Gateway\FirstData;

class ConnectRequestFields
{
    const NAME                   = 'bname';
    const CARD_FUNCTION          = 'cardFunction';
    const CARD_NUMBER            = 'cardnumber';
    const CHARGE_TOTAL           = 'chargetotal';
    const COMMENTS               = 'comments';
    const CURRENCY               = 'currency';
    const CVV                    = 'cvm';
    const DYNAMIC_MERCHANT_NAME  = 'dynamicMerchantName';
    const EXP_MONTH              = 'expmonth';
    const EXP_YEAR               = 'expyear';
    const HASH                   = 'hash';
    const HASH_ALGORITHM         = 'hash_algorithm';
    const INVOICE_NUMBER         = 'invoicenumber';
    const LANGUAGE               = 'language';
    const MODE                   = 'mode';
    const MERCHANT_TXN_ID        = 'merchantTransactionId';
    const NUMBER_OF_INSTALLMENTS = 'numberOfInstallments';
    const ORDER_ID               = 'oid';
    const PAYMENT_METHOD         = 'paymentMethod';
    const RESPONSE_FAIL_URL      = 'responseFailURL';
    const RESPONSE_SUCCESS_URL   = 'responseSuccessURL';
    const STORE_NAME             = 'storename';
    const TXN_DATE_TIME          = 'txndatetime';
    const TXN_TYPE               = 'txntype';
    const TIME_ZONE              = 'timezone';

    // For recurring payments
    const TOKEN                  = 'hosteddataid';
}
