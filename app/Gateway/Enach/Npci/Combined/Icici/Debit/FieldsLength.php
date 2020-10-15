<?php

namespace RZP\Gateway\Enach\Npci\Combined\Icici\Debit;

class FieldsLength
{
    const ACH_TRANSACTION_CODE                  =  2;
    const CONTROL_7                             =  7;
    const DESTINATION_ACCOUNT_TYPE              =  2;
    const LEDGER_FOLIO_NUMBER                   =  3;
    const CONTROL_15                            = 15;
    const BENEFICIARY_ACCOUNT_HOLDER_NAME       = 40;
    const CONTROL_9                             =  9;
    const USER_NAME                             = 20;
    const CONTROL_13                            = 13;
    const AMOUNT                                = 13;
    const ACH_ITEM_SEQ_NO                       = 10;
    const CHECKSUM                              = 10;
    const FLAG                                  =  1;
    const REASON_CODE                           =  2;
    const DESTINATION_BANK_IFSC                 = 11;
    const BENEFICIARY_BANK_ACCOUNT_NUMBER       = 35;
    const SPONSER_BANK_IFSC                     = 11;
    const USER_NUMBER                           = 18;
    const TRANSACTION_REFERENCE                 = 30;
    const PRODUCT_TYPE                          =  3;
    const BENEFICIARY_AADHAR_NUMBER             = 15;
    const UMRN                                  = 20;
    const FILLER                                =  7;
}
