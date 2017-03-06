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

    public function fetchFeesBreakupForInvoice($merchantId, $from, $to)
    {
        $feeBreakupAmount = $this->manager
                                ->fee_breakup
                                ->getAttributeWithTableName(Entity::AMOUNT);

        $feeBreakupTransactionId = $this->manager
                                        ->fee_breakup
                                        ->getAttributeWithTableName(Entity::TRANSACTION_ID);

        $transactionId = $this->manager
                              ->transaction
                              ->getAttributeWithTableName(Transaction\Entity::ID);

        $entityId = $this->manager
                         ->transaction
                         ->getAttributeWithTableName(Transaction\Entity::ENTITY_ID);

        $transactionMerchantId = $this->manager
                                      ->transaction
                                      ->getAttributeWithTableName(Transaction\Entity::MERCHANT_ID);

        $type = $this->manager
                     ->transaction
                     ->getAttributeWithTableName(Transaction\Entity::TYPE);

        $transactionCreatedAt = $this->manager
                                     ->transaction
                                     ->getAttributeWithTableName(Transaction\Entity::CREATED_AT);

        $paymentId = $this->manager
                          ->payment
                          ->getAttributeWithTableName(Payment\Entity::ID);

        $capturedAt = $this->manager
                           ->payment
                           ->getAttributeWithTableName(Payment\Entity::CAPTURED_AT);

        $feesBreakup = $this->newQuery()
                       ->selectRaw(Entity::NAME . ','.
                                'SUM(' .$feeBreakupAmount .') AS sum')
                       ->join(Table::TRANSACTION, $feeBreakupTransactionId, '=', $transactionId)
                       ->join(Table::PAYMENT, $entityId, '=', $paymentId)
                       ->where($transactionMerchantId, $merchantId)
                       ->where($type, 'payment')
                       ->whereNotNull($capturedAt)
                       ->whereBetween($transactionCreatedAt, [$from, $to])
                       ->groupBy(Entity::NAME)
                       ->get();

        return $feesBreakup;
    }
}
