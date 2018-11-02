<?php

namespace RZP\Gateway\Netbanking\Idfc;

class TransactionDetails
{
    const TYPE_PAYMENT          = 'Payment';
    const TYPE_VERIFICATION     = 'Verification';

    const STATUS_SUCCESS        = 'SUCCESS';
    const STATUS_FAILURE        = 'FAILURE';

    const PAYMENT_SUCCESS           = 'Y';
    const PAYMENT_FAILED            = 'N';
}
