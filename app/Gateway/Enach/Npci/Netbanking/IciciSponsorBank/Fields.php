<?php

namespace RZP\Gateway\Enach\Npci\Netbanking\IciciSponsorBank;

class Fields
{
    // Constant values used in NACH debit request
    const ACH_TRANSACTION_CODE                            = '67';
    const CONTROL_9                                       = '         ';
    const LEDGER_FOLIO_NUMBER                             = '   ';
    const CONTROL_15                                      = '               ';
    const CONTROL_7                                       = '       ';
    const CONTROL_13                                      = '             ';
    const ACH_ITEM_SEQ_NUMBER                             = '          ';
    const CHECK_SUM                                       = '          ';
    const FLAG                                            = ' ';
    const REASON_CODE                                     = '  ';
    const PRODUCT_TYPE                                    = '10 ';
    const BENEFICIARY_AADHAR_NUMBER                       = '               ';
    const FILLER                                          = '       ';
    const END_TIMESTAMP                                   = 'end_timestamp';
    const START_TIMESTAMP                                 = 'start_timestamp';
    const FREQUENCY                                       = 'As & when Presented';

    //Constant values used in Presentation file headings
    const ACH_TRANSACTION_CODE_HEADING                    = '56';
    const CONTROL_7_HEADING                               = '       ';
    const USERNAME_HEADING                                = 'RAZORPAY                                ';
    const ACH_FILE_NUMBER_HEADING                         = '         ';
    const CONTROL_14_HEADING                              = '              ';
    const CONTROL_9_HEADING                               = '         ';
    const CONTROL_15_HEADING                              = '               ';
    const LEDGER_FOLIO_NUMBER_HEADING                     = '   ';
    const ACH_ITEM_SEQ_NUMBER_HEADING                     = '          ';
    const CHECK_SUM_HEADING                               = '          ';
    const FILLER_3                                        = '   ';
    // TODO: confirm with product
    const USER_DEFINED_LIMIT_FOR_INDIVIDUAL_ITEMS         = '0001000000000';
    const SAVINGS                                         = 'savings';
    const CURRENT                                         = 'current';
    const USER_REFERENCE_HEADING                          = '000000000000000000';
    const USER_BANK_ACCOUNT_NUMBER_HEADING                = '000205025290                       ';
    const SETTLEMENT_CYCLE_HEADING                        = '  ';
    const FILLER_57                                       = '                                                         ';

    const ACCOUNT_TYPE_VALUE                             =  'accountType' ;
    const ACCOUNT_NAME                                   =  'accountName';
    const USERNAME                                       =  'userName';
    const AMOUNT                                         =  'amount';
    const IFSC                                           =  'ifsc';
    const ACCOUNT_NUMBER                                 =  'accountNumber';
    const UMRN                                           =  'umrn';
    const UTILITY_CODE                                   =  'utilityCode';
    const TRANSACTION_REFERENCE                          =  'transactionReference';
    const SPONSER_BANK                                   =  'sponserBank';
}
