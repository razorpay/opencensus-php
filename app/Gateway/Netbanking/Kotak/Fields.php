<?php

namespace RZP\Gateway\Netbanking\Kotak;

class Fields
{
    const AUTHORIZATION_STATUS = 'AuthorizationStatus';

    const AMOUNT               = 'TxnAmount';

    const BANK_REFERENCE_NO    = 'BankReference';

    const STATUS               = 'status';

    const TEST_SCOPE           = 'oob';

    const LIVE_SCOPE           = 'kbsecpg';

    protected $fields = array(
        'MerchantCode',
        'Date',
        'MerchantRefNo',
        'ClientCode',
        'SuccessSaticFlag',
        'FailureStaticFlag',
        'TxnAmount',
        'TransactionId',
        'Ref1',
        'flgVerify',
        'BankRefNo',
        'flgSuccess',
        'Message',
    );
}
