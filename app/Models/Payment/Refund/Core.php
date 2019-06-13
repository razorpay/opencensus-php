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
     * Refunds of the the merchant ids listed here will be expose the public status
     * Refunds of only the merchant ids with true will be directed to scrooge to fetch the public status
     *
     * @var array
     */
    public static $refundsPublicStatusMerchantsViaScrooge = [
        '9DZkE60krEG4wq' => true,
        '9ncOh0EZ8sC9z9' => true,
        '9hefgkvGhT18Q9' => true,
        'BbaYzzPW541Aut' => true,
        '80oXBj51MHGmwH' => true,
        '94tLpgbojcR85O' => true,
        'C1fjEduvEkBUEK' => true,
        'C1fmOZYiZiezoD' => true,
        'C1fnUMHBmitlPB' => true,
        'C1fo6ARXco94tP' => true,
        'C1fp6DAnDH4YUz' => true,
        'C1fq8jgl8NRKnh' => true,
        'CToqTdhmF5b4bx' => true,
        'CTq2cnNAs3Qxeo' => true,
        'CTqJ4as5l5X6iQ' => true,
        'CTqQPHMDvKRab6' => true,
        'CUt2G8y6WttO2g' => true,
        'CUsPNux3ZRGMEO' => true,
        'CUsSCFbU2Rg4zr' => true,
        'CUsroOupiUIEK2' => true,
        'CUsv44mEYn5oyV' => true,
        'CUszQfJSmGEXwH' => true,
        'BoE6Rycqadwtvh' => false,
        'CBcPtPwFgpjdUp' => false,
        'ByWbZS28NK9CeG' => false,
        'BREsAWr9hzga0n' => false,
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
                        Entity::MODE        => $ftaData['mode'],
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

    public static function getRefundsPublicStatusMerchantsViaScrooge(): array
    {
        return self::$refundsPublicStatusMerchantsViaScrooge;
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
        return array_key_exists($merchantId, self::getRefundsPublicStatusMerchantsViaScrooge());
    }

    /**
     * This function checks if a given refund's public status must be fetched from scrooge,
     * default is true (since there is a feature flag as well),
     * unless mentioned false in the $refundsPublicStatusMerchants
     *
     * @param $merchantId
     * @return bool
     *
     */
    public static function fetchPublicStatusFromScrooge(string $merchantId): bool
    {
        $fetchPublicStatusFromScrooge = true;

        if ((self::isRefundsPublicStatusMerchant($merchantId) === true) and
            (self::getRefundsPublicStatusMerchantsViaScrooge()[$merchantId] === false))
        {
            $fetchPublicStatusFromScrooge = false;
        }

        return $fetchPublicStatusFromScrooge;
    }
}
