<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

// All fields sent as parameters in the request
class RequestFields
{
    const MERCHANT_ID = 'MerchantID';
    const REQUEST_XML = 'MandateReqDoc';
    const CHECKSUM    = 'CheckSumVal';
    const BANK_ID     = 'BankID';

    // Verify Request
    const MANDATE_REQ_ID_LIST = 'mandateReqIDList';
    const MANDATE_ID          = 'MndtReqId';
    const REQ_INIT_DATE       = 'ReqInitDate';
}
