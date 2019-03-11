<?php

namespace RZP\Gateway\Paysecure;

class StatusCode
{
    const SUCCESS    = 'success';
    const FAILURE    = 'failure';

    const CALLBACK_SUCCESS = 'ACCU000';

    const IFRAME_CALLBACK_SUCCESS = 'ISSUER000';

    const TRANSACTION_STATUS_PIN_ACQUIRED   = 'AQ';
    const TRANSACTION_STATUS_AUTHORIZED     = 'AZ';
    const TRANSACTION_STATUS_DECLINED       = 'DC';
    const TRANSACTION_STATUS_INITIATED      = 'I';
    const TRANSACTION_STATUS_PRIOR_TO_EFT   = 'PE';
}
