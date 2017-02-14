<?php

namespace RZP\Models\Transaction\FeeBreakup;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Payment;
use RZP\Models\Transaction;

class Repository extends Base\Repository
{
    protected $entity = 'fee_breakup';

    protected $appFetchParamRules = array(
        Entity::TRANSACTION_ID          => 'sometimes|alpha_num|size:14',
        Entity::PRICING_RULE_ID         => 'sometimes|alpha_num|size:14',
    );

    public function fetchFeesBreakupInvoice($merchantId, $from, $to)
    {
        $feesBreakup = $this->newQuery()
                       ->selectRaw(Entity::NAME . ','.
                                'SUM(fees_breakup.amount) AS sum')
                       ->join(Table::TRANSACTION, 'fees_breakup.transaction_id', '=', 'transactions.id')
                       ->join(Table::PAYMENT, 'transactions.entity_id', '=', 'payments.id')
                       ->where('transactions.merchant_id', $merchantId)
                       ->where('transactions.type', 'payment')
                       ->whereNotNull('payments.captured_at')
                       ->whereBetween('transactions.created_at', [$from, $to])
                       ->groupBy(Entity::NAME)
                       ->get();

        return $feesBreakup;
    }
}
