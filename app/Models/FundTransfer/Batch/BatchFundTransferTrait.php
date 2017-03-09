<?php

namespace RZP\Models\FundTransfer\Batch;

use RZP\Exception;

trait BatchFundTransferTrait
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
            $this->batchSettlement->incrementTotalCount();
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

    protected function createBatchSettlementEntity($entity, $txnsCount) : Entity
    {
        $batchSettlement = new Entity;

        $input = [
            Entity::TYPE              => $entity->getEntity(),
            Entity::CHANNEL           => $entity->getChannel(),
            Entity::AMOUNT            => $entity->getAmount(),
            Entity::FEES              => $entity->getFees(),
            Entity::SERVICE_TAX       => $entity->getServiceTax(),
            Entity::TOTAL_COUNT       => 1,
            Entity::TRANSACTION_COUNT => $txnsCount,
            Entity::INITIATED_AT      => time(),
            Entity::API_FEE           => 0,
            Entity::GATEWAY_FEE       => 0,
            Entity::URLS              => null,
        ];

        $batchSettlement->build($input);

        return $batchSettlement;
    }
}
