<?php

namespace RZP\Models\Pricing\FeeBreakup;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Transaction;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'fee_breakup';

    public function fetchFeesBreakupInvoice($merchantId, $from, $to)
    {
        $txnIds = (new Transaction\Repository)->getTransactionForReport($merchantId, $from, $to);

        $rzpFees = $this->newQuery()
                         ->whereIn('fees_breakup.transaction_id', $txnIds)
                         ->where('fees_breakup.name', '=', 'razorpay')
                         ->sum('fees_breakup.amount');

        $serviceTax = $this->newQuery()
                         ->whereIn('fees_breakup.transaction_id', $txnIds)
                         ->where('fees_breakup.name', '=', 'service_tax')
                         ->sum('fees_breakup.amount');

        $swachhBharatCess = $this->newQuery()
                         ->whereIn('fees_breakup.transaction_id', $txnIds)
                         ->where('fees_breakup.name', '=', 'swachh_bharat_cess')
                         ->sum('fees_breakup.amount');

        $krishiKalyanCess = $this->newQuery()
                         ->whereIn('fees_breakup.transaction_id', $txnIds)
                         ->where('fees_breakup.name', '=', 'krishi_kalyan_cess')
                         ->sum('fees_breakup.amount');

        return [
            'rzp_fee'                          =>  (int) $rzpFees,
            'service_tax'                      =>  (int) $serviceTax,
            'swachh_bharat_cess'               =>  (int) $swachhBharatCess,
            'krishi_kalyan_cess'               =>  (int) $krishiKalyanCess,
        ];
    }

}
