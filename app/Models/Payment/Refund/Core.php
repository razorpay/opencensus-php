<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Payment\Refund\Status;

class Core extends Base\Core
{
    /**
     * Updates refund entity status after FTA recon
     *
     * @param Entity $refund
     * @param array  $ftaData
     */
    public function updateStatusAfterFtaRecon(Entity $refund, array $ftaData)
    {
        switch ($ftaData[Attempt\Constants::FTA_STATUS]) {
            case Attempt\Status::PROCESSED:
                $refund->setStatusProcessed();
                $this->repo->saveOrFail($refund);
                break;

            case Attempt\Status::FAILED:
                $refund->setStatus(Status::FAILED);
                $this->repo->saveOrFail($refund);
                break;

            case Attempt\Status::CREATED:
                break;

            case Attempt\Status::INITIATED:
                break;

            default:
                $this->trace->error(
                    TraceCode::UNKNOWN_FTA_STATUS_SENT_TO_REFUND,
                    $ftaData);
        }
    }

    public function updateStatusAfterFtaInitiated(Entity $entity, Attempt\Entity $fta)
    {
        $entity->batchFundTransfer()->associate($fta->batchFundTransfer);

        $entity->setStatus(Status::INITIATED);

        $this->repo->saveOrFail($entity);
    }

    public function updateWithDetailsBeforeFtaRecon(Entity $entity, array $ftaData)
    {
        $entity->setUtr($ftaData[Attempt\Constants::UTR]);

        $entity->setRemarks($ftaData[Attempt\Constants::REMARKS]);

        $this->trace->info(
            TraceCode::FTA_RECON_SOURCE_UPDATED,
            [
                'source_id'         => $entity->getId(),
                'fta_id'            => $ftaData[Attempt\Constants::FTA_ID],
                'source_original'   => $entity->getOriginalAttributesAgainstDirty(),
                'source_dirty'      => $entity->getDirty(),
            ]);

        $this->repo->saveOrFail($entity);
    }
}
