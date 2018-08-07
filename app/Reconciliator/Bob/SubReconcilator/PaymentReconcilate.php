<?php

namespace RZP\Reconciliator\Bob;

use Carbon\Carbon;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/

    const TRANSACTION_DATE = 'Transaction Date';
    const TRANSACTION_TIME = 'Transaction Time';
    const SETTLEMENT_DATE  = 'Settlement Date';
    const MERCHANT_TYPE    = 'Merchant Type';


}
