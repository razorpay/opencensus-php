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
                         ->whereIn(Entity::TRANSACTION_ID, $txnIds)
                         ->where(Entity::NAME, '=', Name::RZP)
                         ->sum(Entity::AMOUNT);

        $serviceTax = $this->newQuery()
                         ->whereIn(Entity::TRANSACTION_ID, $txnIds)
                         ->where(Entity::NAME, '=', Name::SERVICE_TAX)
                         ->sum(Entity::AMOUNT);

        $swachhBharatCess = $this->newQuery()
                         ->whereIn(Entity::TRANSACTION_ID, $txnIds)
                         ->where(Entity::NAME, '=', Name::SWACHH_BHARAT_CESS)
                         ->sum(Entity::AMOUNT);

        $krishiKalyanCess = $this->newQuery()
                         ->whereIn(Entity::TRANSACTION_ID, $txnIds)
                         ->where(Entity::NAME, '=', Name::KRISHI_KALYAN_CESS)
                         ->sum(Entity::AMOUNT);

        return [
            'rzp_fee'                          =>  (int) $rzpFees,
            'service_tax'                      =>  (int) $serviceTax,
            'swachh_bharat_cess'               =>  (int) $swachhBharatCess,
            'krishi_kalyan_cess'               =>  (int) $krishiKalyanCess,
        ];
    }

}
