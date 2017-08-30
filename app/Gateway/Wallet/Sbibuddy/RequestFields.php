<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

class RequestFields
{
    const EXTERNAL_TRANSACTION_ID   = 'externalTransactionId';
    const ORDER_ID                  = 'orderId';
    const AMOUNT                    = 'amount';
    const CURRENCY                  = 'currency';
    const CALLBACK_URL              = 'callbackUrl';
    const BACK_URL                  = 'backUrl';
    const DESCRIPTION               = 'description';
    const CATEGORY                  = 'category';
    const SUBCATEGORY               = 'subcategory';
    const TRANSACTION_ID            = 'transactionId';
    const PROCESSOR_ID              = 'processorId';

    const REFUND_FEE                = 'refundFee';
    const REFUND_REQUEST_ID         = 'refundRequestId';

    const MERCHANT_ID               = 'merchantId';
    const ENCRYPTED_DATA            = 'encryptedData';

}
