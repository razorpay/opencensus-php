<?php

namespace RZP\Models\FundTransfer\Batch;

use RZP\Exception;

trait BatchFundTransferTrait
{
    protected $batchFundTransfer = null;

    protected function createOrUpdateBatchFundTransferForEntity($entity, int $txnsCount)
    {
        if ($this->batchFundTransfer === null)
        {
            $this->batchFundTransfer = $this->createBatchFundTransferEntity($entity, $txnsCount);
        }
        else
        {
            $this->batchFundTransfer->incrementAmount($entity->getAmount());
            $this->batchFundTransfer->incrementFees($entity->getFees());
            //$this->batchFundTransfer->incrementServiceTax($entity->getServiceTax());
            $this->batchFundTransfer->incrementTax($entity->getTax());
            $this->batchFundTransfer->incrementTotalCount();
            $this->batchFundTransfer->incrementTransactionCount($txnsCount);
        }

        $this->repo->saveOrFail($this->batchFundTransfer);
    }

    protected function updateFileDetailsInBatchFundTransferEntity(array $details)
    {
        if ($this->batchFundTransfer === null)
        {
            throw new Exception\LogicException(
                'Update file details for Batch Settlement attempted before entity creation',
                null,
                [
                    'details' => $details
                ]);
        }

        $this->batchFundTransfer->setUrls($details['urls']);

        $this->batchFundTransfer->setTxtFileId($details['txt_file_id'] ?? null);

        $this->batchFundTransfer->setExcelFileId($details['excel_file_id'] ?? null);

        $this->repo->saveOrFail($this->batchFundTransfer);
    }

    protected function createBatchFundTransferEntity($entity, $txnsCount) : Entity
    {
        $batchFundTransfer = new Entity;

        $input = [
            Entity::TYPE              => $entity->getEntity(),
            Entity::CHANNEL           => $entity->getChannel(),
            Entity::AMOUNT            => $entity->getAmount(),
            Entity::FEES              => $entity->getFees(),
            //Entity::SERVICE_TAX       => $entity->getServiceTax(),
            Entity::TAX               => $entity->getTax(),
            Entity::TOTAL_COUNT       => 1,
            Entity::TRANSACTION_COUNT => $txnsCount,
            Entity::INITIATED_AT      => time(),
            Entity::API_FEE           => 0,
            Entity::GATEWAY_FEE       => 0,
            Entity::URLS              => null,
        ];

        $batchFundTransfer->build($input);

        return $batchFundTransfer;
    }
}
