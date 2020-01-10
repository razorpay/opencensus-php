<?php

namespace RZP\Gateway\Enach\Citi;

class NachDebitFileHeadings
{
    // Debit Request file headings
    const ACH_TRANSACTION_CODE                  = 'ACH Transaction Code';
    const CONTROL_9S                            = 'Control_9S';
    const DESTINATION_ACCOUNT_TYPE              = 'Destination Account Type';
    const LEDGER_FOLIO_NUMBER                   = 'Ledger Folio Number';
    const CONTROL_15S                           = 'Control_15S';
    const BENEFICIARY_ACCOUNT_HOLDER_NAME       = 'Beneficiary Account Holder\'s Name';
    const CONTROL_9SS                           = 'Control_9SS';
    const CONTROL_7S                            = 'Control_7S';
    const USER_NAME                             = 'User Name';
    const CONTROL_13S                           = 'Control_13S';
    const AMOUNT                                = 'Amount';
    const ACH_ITEM_SEQ_NO                       = 'ACH Item Seq No.';
    const CHECKSUM                              = 'Checksum';
    const FLAG                                  = 'Flag for success / return';
    const REASON_CODE                           = 'Reason Code';
    const DESTINATION_BANK_IFSC                 = 'Destination Bank IFSC / MICR / IIN';
    const BENEFICIARY_BANK_ACCOUNT_NUMBER       = 'Beneficiary\'s Bank Account number';
    const SPONSOR_BANK_IFSC                     = 'Sponsor Bank IFSC / MICR / IIN';
    const USER_NUMBER                           = 'User Number';
    const TRANSACTION_REFERENCE                 = 'Transaction Reference';
    const PRODUCT_TYPE                          = 'Product Type';
    const BENEFICIARY_AADHAR_NUMBER             = 'Beneficiary Aadhaar Number';
    const UMRN                                  = 'UMRN';
    const FILLER                                = 'Filler';
    const CONTROL_7Z                            = 'Control_7Z';
    const CONTROL_14Z                           = 'Control_14Z';
    const ACH_FILE_NUMBER                       = 'ACH file number';
    const MAX_AMOUNT                            = 'Max Amount';
    const FILLER_3                              = 'filler_3';
    const SIZE                                  = 'size';
    const SETTLEMENT_CYCLE                      = 'Settlement Cycle';
    const FILLER_57                             = 'filler_57';

    //Summary file Headings
    const UTILITY_CODE                          = 'Utility Code';
    const NO_OF_RECORDS                         = 'No. of Records';
    const TOTAL_AMOUNT                          = 'Total Amount';
    const SETTLEMENT_DATE                       = 'Settlement Date';
}
