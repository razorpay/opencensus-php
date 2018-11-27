<?php

namespace RZP\Gateway\Paysecure;

class Constants
{
    // Paysecure Version
    const VERSION           = '1.0.0.0';

    // Commands
    const COMMAND_CHECKBIN2          = 'checkbin2';
    const COMMAND_INITIATE           = 'initiate';
    const COMMAND_INITIATE_2         = 'initiate2';
    const COMMAND_AUTHORIZE          = 'authorize';
    const COMMAND_TRANSACTION_STATUS = 'transactionstatus';

    // Statuses
    const STATUS_SUCCESS    = 'success';
    const STATUS_FAILURE    = 'failure';

    const STATUS_CALLBACK_SUCCESS = 'ACCU000';

    const VALUE_TRUE  = 'TRUE';
    const VALUE_FALSE = 'FALSE';

    const TRANSACTION_STATUS_PIN_ACQUIRED   = 'AQ';
    const TRANSACTION_STATUS_AUTHORIZED     = 'AZ';
    const TRANSACTION_STATUS_DECLINED       = 'DC';
    const TRANSACTION_STATUS_INITIATED      = 'I';
    const TRANSACTION_STATUS_PRIOR_TO_EFT   = 'PE';
}
