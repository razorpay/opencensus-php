<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

class RequestFields
{
    const MERCHANT_ID = 'MerchantID';
    const REQUEST_XML = 'MandateReqDoc';
    const CHECKSUM    = 'CheckSumVal';
    const BANK_ID     = 'BankID';
    const AUTH_MODE   = 'AuthMode';
    const SPID        = 'SPID';

    // Verify Request
    const MANDATE_REQ_ID_LIST = 'mandateReqIDList';
    const MANDATE_ID          = 'MndtReqId';
    const REQ_INIT_DATE       = 'ReqInitDate';
}
