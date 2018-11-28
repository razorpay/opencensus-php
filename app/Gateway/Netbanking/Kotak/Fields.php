<?php

namespace RZP\Gateway\Netbanking\Kotak;

class Fields
{
    const AUTHORIZATION_STATUS = 'AuthorizationStatus';

    const AMOUNT               = 'TxnAmount';

    const BANK_REFERENCE_NO    = 'BankReference';

    const STATUS               = 'status';

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
