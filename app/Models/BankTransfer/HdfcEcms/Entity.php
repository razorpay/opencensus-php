<?php

namespace RZP\Models\BankTransfer\HdfcEcms;

class Entity
{
    const TRANSACTION_DATE        = 'Transaction_Date';
    const ACCOUNT_NUMBER          = 'Account_Number';
    const CLIENT_CODE             = 'Client_Code';
    const VIRTUAL_ACCOUNT_NO      = 'Virtual_Account_No';
    const BENE_NAME               = 'Bene_Name';
    const TRANSACTION_DESCRIPTION = 'Transaction_Description';
    const DEBIT_CREDIT            = 'Debit_Credit';
    const CHEQUE_NO               = 'Cheque_No';
    const REFERENCE_NO            = 'Reference_No';
    const AMOUNT                  = 'Amount';
    const TYPE                    = 'Type';
    const REMITTER_IFSC           = 'Remitter_IFSC';
    const REMITTER_BANK_NAME      = 'Remitter_Bank_Name';
    const REMITTING_BANK_BRANCH   = 'Remitting_Bank_Branch';
    const REMITTER_ACCOUNT_NO     = 'Remitter_Account_No';
    const REMITTER_NAME           = 'Remitter_Name';
    const USER_ID                 = 'UserID';
    const UNIQUE_ID               = 'UniqueID';

    const RESPONSE_STATUS = 'Status';
    const RESPONSE_REASON = 'Reason';
    const TRANSACTION_ID  = 'transaction_id';
}
