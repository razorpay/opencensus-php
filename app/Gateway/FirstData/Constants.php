<?php

namespace RZP\Gateway\FirstData;

class Constants
{
    const TXN_TYPE                          = 'txntype';
    const TIME_ZONE                         = 'timezone';
    const TXN_DATE_TIME                     = 'txndatetime';
    const HASH_ALGORITHM                    = 'hash_algorithm';
    const HASH                              = 'hash';
    const STORE_NAME                        = 'storename';
    const MODE                              = 'mode';
    const CHARGE_TOTAL                      = 'chargetotal';
    const CURRENCY                          = 'currency';
    const ORDER_ID                          = 'oid';
    const TDATE                             = 'tdate';
    const NAME                              = 'bname';
    const PAYMENT_METHOD                    = 'paymentMethod';
    const CUSTOMER_ID                       = 'customerid';
    const INVOICE_NUMBER                    = 'invoicenumber';
    const CARD_FUNCTION                     = 'cardFunction';
    const COMMENTS                          = 'comments';
    const RESPONSE_SUCCESS_URL              = 'responseSuccessURL';
    const RESPONSE_FAIL_URL                 = 'responseFailURL';
    const DYNAMIC_MERCHANT_NAME             = 'dynamicMerchantName';
    const LANGUAGE                          = 'language';
    const HASH_EXTENDED                     = 'hashExtended';
    const NUMBER_OF_INSTALLMENTS            = 'numberOfInstallments';
    const TRX_ORIGIN                        = 'trxOrigin';
    const DCC_INQUIRY_ID                    = 'dccInquiryId';

    const CARD_NUMBER                       = 'cardnumber';
    const EXP_MONTH                         = 'expmonth';
    const EXP_YEAR                          = 'expyear';
    const CVV                               = 'cvm';

    const APPROVAL_CODE                     = 'approval_code';
    const RESPONSE_HASH                     = 'response_hash';
    const ORDER_REQUEST                     = 'IPGApiOrderRequest';
    const ACTION_REQUEST                    = 'IPGApiActionRequest';

    const TRANSACTION_RESULT                = 'TransactionResult';
    const TRANSACTION_STATE                 = 'TransactionState';
    const TRANSACTION_VALUES                = 'TransactionValues';

    const V1_CREDITCARDTXTYPE               = 'v1:CreditCardTxType';
    const V1_TYPE                           = 'v1:Type';
    const V1_PAYMENT                        = 'v1:Payment';
    const V1_CHARGETOTAL                    = 'v1:ChargeTotal';
    const V1_CURRENCY                       = 'v1:Currency';
    const V1_TRANSACTIONDETAILS             = 'v1:TransactionDetails';
    const V1_ORDERID                        = 'v1:OrderId';
    const V1_TRANSACTION                    = 'v1:Transaction';

    const TEST_STORE_ID                     = 'test_store_id';
    const TEST_HASH_SECRET                  = 'test_hash_secret';

    const SERVER_CERTIFICATE_PATH           = 'server_certificate_path';
    const CLIENT_CERTIFICATE_PATH           = 'client_certificate_path';
    const CLIENT_CERTIFICATE_KEY_PATH       = 'client_certificate_key_path';

    const PROCESSING                        = 'PROCESSING';
    const SERVICES                          = 'SERVICES';
}
