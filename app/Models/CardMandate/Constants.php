<?php


namespace RZP\Models\CardMandate;


use RZP\Models\Payment\Gateway;

class Constants
{
    const MANDATE_HQ_TRUE                    = 'true';
    const MANDATE_HQ_FALSE                   = 'false';
    const MANDATE_HQ_APPROVED                = 'approved';

    const MANDATE_HUB_MAX_AMOUNT_DEFAULT     = 500000;

    const DEBIT_TYPE_FIXED_AMOUNT    = 'fixed_amount';
    const DEBIT_TYPE_VARIABLE_AMOUNT = 'variable_amount';

    // Mainly for Optimizer
    const OPTIMIZER_HUBS =[Gateway::PAYU];
}
