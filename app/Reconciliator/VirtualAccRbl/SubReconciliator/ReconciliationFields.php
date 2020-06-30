<?php

namespace RZP\Reconciliator\VirtualAccRbl\SubReconciliator;

class ReconciliationFields
{
    const TRANSACTION_TYPE              = 'transaction_type';

    const TRAN_ID                       = 'tran_id';

    const AMOUNT                        = 'amount';

    const UTR_NUMBER                    = 'utr_number';

    const RRN_NUMBER                    = 'rrn_number';

    const SENDER_IFSC                   = 'sender_ifsc';

    const SENDER_ACCOUNT_NUMBER         = 'sender_acct_number';

    const SENDER_ACCOUNT_TYPE           = 'sender_acct_type';

    const SENDER_ACCOUNT_NAME           = 'sender_acct_name';

    const BENEFICIARY_ACCOUNT_TYPE      = 'benef_acct_type';

    const BENEFICIARY_ACCOUNT_NUMBER    = 'benef_acct_number';

    const BENEF_NAME                    = 'benef_name';

    const CREDIT_DATE                   = 'credit_date';

    const CREDIT_ACCOUNT_NUMBER         = 'credit_acct_number';

    const CORPORATE_CODE                = 'corporate_code';

    const SENDER_INFORMATION            = 'sender_information';
}
