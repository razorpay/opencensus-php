<?php

namespace RZP\Models\Payment\Refund;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Admin\ConfigKey;

class Core extends Base\Core
{
    /**
     * Refunds of only these merchant ids will be directed to scrooge.
     *
     * @var array
     */
    public static $refundsPublicStatusMerchants = [
        '9DZkE60krEG4wq',
        '9ncOh0EZ8sC9z9',
        '9hefgkvGhT18Q9',
        'BbaYzzPW541Aut',
        '80oXBj51MHGmwH',
        '94tLpgbojcR85O',
        'C1fjEduvEkBUEK',
        'C1fmOZYiZiezoD',
        'C1fnUMHBmitlPB',
        'C1fo6ARXco94tP',
        'C1fp6DAnDH4YUz',
        'C1fq8jgl8NRKnh',
    ];

    /**
     * Updates refund entity status after FTA recon
     *
     * @param Entity $refund
     * @param array  $ftaData
     */
    public function updateStatusAfterFtaRecon(Entity $refund, array $ftaData)
    {
        switch ($ftaData[Attempt\Constants::FTA_STATUS])
        {
            case Attempt\Status::PROCESSED:
                if ($refund->isScrooge() === true)
                {
                    $data = [
                        Entity::STATUS      => Status::PROCESSED,
                        Entity::REFERENCE1  => $refund->getReference1(),
                    ];

                    (new Service)->makeScroogeEditRefundRequest($refund, $data);
                }
                else
                {
                    $refund->setStatusProcessed();
                    $refund->setGatewayRefunded(true);
                    $this->repo->saveOrFail($refund);
                }
                break;

            case Attempt\Status::FAILED:
                if ($refund->isScrooge() === true)
                {
                    $data = [
                        Entity::STATUS      => Status::FAILED,
                        // Reference1 is set as part of FTA row processor (status cron)
                        Entity::REFERENCE1  => $refund->getReference1(),
                        Entity::REFERENCE2  => $refund->getReference2(),
                    ];

                    //
                    // If fta gets failed, resetting fta related data here. This can be processed by payment gateway
                    // later.
                    //
                    $refund->setBatchFundTransferId(null);
                    $refund->setUtr(null);
                    $refund->setRemarks(null);
                    $this->repo->saveOrFail($refund);

                    (new Service)->makeScroogeEditRefundRequest($refund, $data, 'file_init_event');
                }
                else
                {
                    $refund->setStatus(Status::FAILED);
                    $this->repo->saveOrFail($refund);
                }
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

    public function updateEntityWithFtsTransferId(Entity $entity, $ftsTransferId)
    {
        $entity->setFTSTransferId($ftsTransferId);

        $this->repo->saveOrFail($entity);
    }

    public static function getRefundsPublicStatusMerchants(): array
    {
        return self::$refundsPublicStatusMerchants;
    }

    /**
     * This function checks if a given merchant is to be shown refund's Public status.
     *
     * @param $merchantId
     * @return bool
     *
     */
    public static function isRefundsPublicStatusMerchant(string $merchantId): bool
    {
        return (in_array($merchantId, self::getRefundsPublicStatusMerchants(), true) === true);
    }
}
