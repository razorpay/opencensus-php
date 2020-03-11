<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

// All Fields that are used while preparing the xml request
class RequestNpciTags
{
    const MESSAGE_ID            = 'MsgId';
    const CREATION_DATE_TIME    = 'CreDtTm';
    const MID                   = 'Id';
    const CATEGORY_CODE         = 'CatCode';
    const UTILITY_CODE          = 'UtilCode';
    const CATEGORY_DESCRIPTION  = 'CatDesc';
    const NAME                  = 'Name';
    const MANDATE_ID            = 'MndtReqId';
    const SEQUENCE_TYPE         = 'SeqTp';
    const FREQUENCY             = 'Frqcy';
    const FIRST_COLLECTION_DATE = 'FrstColltnDt';
    const FINAL_COLLECTION_DATE = 'FnlColltnDt';
    const COLLECTION_AMOUNT     = 'ColltnAmt';
    const MAX_AMOUNT            = 'MaxAmt';
    const DEBTOR_NAME           = 'Nm';
    const DEBTOR_ACCOUNT        = 'AccNo';
    const CREDITOR_NAME         = 'Nm';
    const CREDITOR_ACCOUNT      = 'AccNo';
    const IFSC_SPONSOR          = 'MmbId';
    const MANDATE_TYPE          = 'Mndt_Type';
    const SPONSORED_BANK_NAME   = 'Spn_Bnk_Nm';
    const ACCOUNT_TYPE          = 'Acct_Type';
}
