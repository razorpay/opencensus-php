<?php

namespace RZP\Models\Batch\Processor;

use RZP\Base\ConnectionType;
use RZP\Models\Batch;
use RZP\Models\Reversal;
use RZP\Models\Transfer;

class LinkedAccountReversal extends Base
{
    protected function processEntry(array & $entry)
    {
        $transferId = trim($entry[Batch\Header::TRANSFER_ID]);

        $parentMerchant = $this->merchant->getParentId();

        if ((new Transfer\Service)->isRouteTidbFetchExpEnabled($parentMerchant))
        {
            $transfer = $this->repo->transfer->fetchByPublicIdAndLinkedAccountMerchant(
                $transferId, $this->merchant, ConnectionType::DATA_WAREHOUSE_MERCHANT);
        }
        else
        {
            $transfer = $this->repo->transfer->fetchByPublicIdAndLinkedAccountMerchant($transferId, $this->merchant);
        }

        $input = [
            Reversal\Entity::AMOUNT             => (string) $entry[Batch\Header::AMOUNT_IN_PAISE],
            Reversal\Entity::NOTES              => $entry[Batch\Header::NOTES] ?? [],
            Reversal\Entity::REFUND_TO_CUSTOMER => true
        ];

        $reversal = (new Reversal\Core)->linkedAccountReverseForTransfer($transfer, $input, $this->merchant);

        $reversalArray = $reversal->toArrayPublic();

        // Update the entry with output values
        $entry[Batch\Header::STATUS]          = Batch\Status::SUCCESS;
        $entry[Batch\Header::REVERSAL_ID]     = $reversalArray['id'];
        $entry[Batch\Header::TRANSFER_ID]     = $reversalArray['transfer_id'];
        $entry[Batch\Header::AMOUNT_IN_PAISE] = $reversalArray['amount'];
        $entry[Batch\Header::REFUND_ID]       = $reversalArray['customer_refund_id'];
        $entry[Batch\Header::INITIATOR_ID]    = $reversalArray['initiator_id'];
        $entry[Batch\Header::NOTES]           = $reversalArray['notes'];
    }

    /**
     * Besides what parent's method does:
     * - Sets aggregate processed amount of batch entity.
     *
     * @param $entries
     */
    protected function postProcessEntries(array & $entries)
    {
        parent::postProcessEntries($entries);

        $processedAmount = 0;

        foreach ($entries as $entry)
        {
            if ($entry[Batch\Header::STATUS] === Batch\Status::SUCCESS)
            {
                $processedAmount += $entry[Batch\Header::AMOUNT_IN_PAISE];
            }
        }

        $this->batch->setProcessedAmount($processedAmount);
    }
}
