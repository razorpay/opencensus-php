<?php

namespace RZP\Models\Base\Traits;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement\Batch\Entity as BatchSettlement;
use RZP\Models\Transaction;

trait BatchSettlementTrait
{
    protected $batchSettlement = null;

    protected function createOrUpdateBatchSettlementForEntity($entity, int $txnsCount)
    {
        if ($this->batchSettlement === null)
        {
            $this->batchSettlement = $this->createBatchSettlementEntity($entity, $txnsCount);
        }
        else
        {
            $this->batchSettlement->incrementAmount($entity->getAmount());
            $this->batchSettlement->incrementFees($entity->getFees());
            $this->batchSettlement->incrementServiceTax($entity->getServiceTax());
            $this->batchSettlement->incrementSettlementCount();
            $this->batchSettlement->incrementTransactionCount($txnsCount);
        }

        $this->repo->saveOrFail($this->batchSettlement);
    }

    protected function updateBatchSettlementEntityUrls(array $urls)
    {
        if ($this->batchSettlement === null)
        {
            throw new Exception\LogicException(
                'Update URLs for Batch Settlement attempted before entity creation',
                null,
                [
                    'urls' => $urls
                ]);
        }

        $this->batchSettlement->setUrls($urls);

        $this->repo->saveOrFail($this->batchSettlement);
    }

    protected function createBatchSettlementEntity($entity, $txnsCount) : BatchSettlement
    {
        $batchSettlement = new BatchSettlement;

        $input = [
            BatchSettlement::CHANNEL           => $entity->getChannel(),
            BatchSettlement::AMOUNT            => $entity->getAmount(),
            BatchSettlement::FEES              => $entity->getFees(),
            BatchSettlement::SERVICE_TAX       => $entity->getServiceTax(),
            BatchSettlement::SETTLEMENT_COUNT  => 1,
            BatchSettlement::TRANSACTION_COUNT => $txnsCount,
            BatchSettlement::INITIATED_AT      => time(),
            BatchSettlement::API_FEE           => 0,
            BatchSettlement::GATEWAY_FEE       => 0,
            BatchSettlement::URLS              => null,
        ];

        $batchSettlement->build($input);

        return $batchSettlement;
    }
}
