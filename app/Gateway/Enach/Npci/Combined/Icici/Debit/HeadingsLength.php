<?php

namespace RZP\Gateway\Enach\Npci\Combined\Icici\Debit;

class HeadingsLength
{
    const ACH_TRANSACTION_CODE                      =  2;
    const CONTROL_7                                 =  7;
    const USER_NAME                                 = 40;
    const CONTROL_14                                = 14;
    const ACH_FILE_NUMBER                           =  9;
    const CONTROL_9                                 =  9;
    const CONTROL_15                                = 15;
    const LEDGER_FOLIO_NUMBER                       =  3;
    const USER_DEFINED_LIMIT_FOR_INDIVIDUAL_ITEMS   = 13;
    const TOTAL_AMOUNT                              = 13;
    const SETTLEMENT_DATE                           =  8;
    const ITEM_SEQ_NO                               = 10;
    const FILLER_3                                  =  3;
    const USER_NUMBER                               = 18;
    const USER_REFERENCE                            = 18;
    const SPONSER_BANK_IFSC                         = 11;
    const USER_BANK_ACCOUNT_NUMBER                  = 35;
    const TOTAL_ITEMS                               =  9;
    const SETTLEMENT_CYCLE                          =  2;
    const FILLER_57                                 = 57;
}
