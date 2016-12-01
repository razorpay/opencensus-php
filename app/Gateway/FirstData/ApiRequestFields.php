<?php

namespace RZP\Gateway\FirstData;

class ApiRequestFields
{
    // Requests to FirstData API are of two types, Order and Action
    // PostAuth (Capture) and Return (Refund) are of type Order
    // Inquiry (Verify) is of type Action

    const ORDER_REQUEST  = 'IPGApiOrderRequest';
    const ACTION_REQUEST = 'IPGApiActionRequest';

    const ACTION         = 'Action';
    const INQUIRY_ORDER  = 'InquiryOrder';
    const ORDER_ID       = 'OrderId';

    // Params in the FirstData APIs are all namespaced.
    // We hardcode the namespaces here to make building the request body easier.

    const V1_CREDIT_CARD_TX_TYPE = 'v1:CreditCardTxType';
    const V1_TYPE                = 'v1:Type';
    const V1_PAYMENT             = 'v1:Payment';
    const V1_CHARGE_TOTAL        = 'v1:ChargeTotal';
    const V1_CURRENCY            = 'v1:Currency';
    const V1_TRANSACTION_DETAILS = 'v1:TransactionDetails';
    const V1_ORDER_ID            = 'v1:OrderId';
    const V1_TRANSACTION         = 'v1:Transaction';
    const V1_TDATE               = 'v1:TDate';

    const A1_ACTION              = 'a1:Action';
    const A1_INQUIRY_ORDER       = 'a1:InquiryOrder';
    const A1_ORDER_ID            = 'a1:OrderId';
}
