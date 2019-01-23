<?php

namespace RZP\Gateway\Netbanking\Sbi;

class ResponseFields
{
    const BANK_REF_NO   = 'sbirefno';

    const AMOUNT        = 'amount';

    const REF_NO        = 'ref_no';

    const STATUS        = 'status';

    const STATUS_DESC   = 'desc';

    const PAYMENT_ID    = 'payment_id';

    const CHECKSUM      = 'checkSum';

    // encrypted data field
    const ENCDATA       = 'encdata';
}
