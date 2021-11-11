<?php

namespace RZP\Models\Payment\Refund;

use App;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Gateway;
use RZP\Models\FundTransfer\Attempt;
use RZP\Reconciliator\Base\InfoCode;

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
        'Ba2to8xoI5kO2x' => false,
        'Cc057xzfWMnyBn' => false,
        'DafY7CuC98D8mK' => false,
        'DwoYjQ5IFWvyJP' => false,
        'DwprllSF0aWzBO' => false,
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
                        Entity::STATUS        => Status::PROCESSED,
                        Entity::REFERENCE1    => $refund->getReference1(),
                        Entity::MODE          => $ftaData[Entity::MODE] ?? Constants::FT_UNKNOWN,
                        Constants::FTA_UPDATE => true,
                    ];

                    (new Service)->makeScroogeEditRefundRequest($refund, $data);
                }
                else
                {
                    $refund->setStatusProcessed();
                    $refund->setGatewayRefunded(true);
                    $this->repo->saveOrFail($refund);
                }

                $processor = $this->getNewProcessor($refund->merchant);

                //
                // Refund FTA is successful and ARN has been updated sending arn updated notification(s)
                //
                if ($processor->isValidArn($refund->getReference1()) === true)
                {
                    $processor->eventRefundArnUpdated($refund);
                }

                break;

            case Attempt\Status::FAILED:
                if ($refund->isScrooge() === true)
                {
                    // Not sending reference1 in cases of failure
                    $data = [
                        Entity::STATUS                     => Status::FAILED,
                        Entity::REFERENCE2                 => $refund->getReference2(),
                        Constants::FTA_UPDATE              => true,
                        Attempt\Entity::BANK_RESPONSE_CODE => $ftaData[Attempt\Entity::BANK_RESPONSE_CODE] ?? null,
                    ];

                    $event = Refund\ScroogeEvents::FILE_INIT_EVENT;

                    //
                    // If fta gets failed, resetting fta related data here. This can be processed by payment gateway
                    // later.
                    //

                    if ($refund->isProcessed() === true)
                    {
                        $event = Refund\ScroogeEvents::PROCESSED_TO_FILE_INIT_EVENT;
                    }

                    $refund->setBatchFundTransferId(null);
                    $refund->setUtr(null);
                    $refund->setRemarks(null);
                    $this->repo->saveOrFail($refund);

                    (new Service)->makeScroogeEditRefundRequest($refund, $data, $event);
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
        if (empty($ftsTransferId) === false)
        {
            $entity->setFTSTransferId($ftsTransferId);

            $this->repo->saveOrFail($entity);
        }
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
        $fetchPublicStatusFromScrooge = false;

        if ((self::isRefundsPublicStatusMerchant($merchantId) === true) and
            (self::getRefundsPublicStatusMerchantsViaScrooge()[$merchantId] === true))
        {
            $fetchPublicStatusFromScrooge = true;
        }

        return $fetchPublicStatusFromScrooge;
    }

    /**
     * This function checks if a given refund's public status is fetched from api
     * checks if merchant id exists and is mapped to false in the $refundsPublicStatusMerchants
     *
     * @param $merchantId
     * @return bool
     *
     */
    public static function fetchPublicStatusFromApi(string $merchantId): bool
    {
        $fetchPublicStatusFromScrooge = false;

        if ((self::isRefundsPublicStatusMerchant($merchantId) === true) and
            (self::getRefundsPublicStatusMerchantsViaScrooge()[$merchantId] === false))
        {
            $fetchPublicStatusFromScrooge = true;
        }

        return $fetchPublicStatusFromScrooge;
    }

    public function reconcileNetbankingRefunds(array $data)
    {
        $refundIds = [];

        foreach ($data as $refundData)
        {
            $gateway = $refundData[E::PAYMENT][Payment\Entity::GATEWAY] ?? null;

            if (in_array($gateway, Gateway::$refundsReconcileNetbankingGateways, true) === true)
            {
                $refundIds[] = $refundData[E::REFUND][Refund\Entity::ID];
            }
        }

        if (empty($refundIds) === false)
        {
            $this->repo->transaction->bulkReconciliationUpdate($refundIds);

            try
            {
                $this->RequestScroogeForReference1Update($refundIds);
            }
            catch (\Exception $e)
            {
                $this->trace->info(TraceCode::RECON_INFO,
                    [
                        'info_code' => InfoCode::RECON_PERSIST_REFERENCE_NUMBER_FAILED,
                        'error_msg' => $e->getMessage()
                    ]);
            }

        }
    }

    /**
     * This function fetches all refund rows by refund ids
     * and send a request to scrooge service to update refunds arn as payment arn
     *
     * @param $refundIds
     */
    protected function RequestScroogeForReference1Update(array $refundIds)
    {
        $refunds = $this->repo->refund->fetchRefundByRefundIds($refundIds);

        // Contain refundId as key and and payment reference1 from payment as value
        // we are doing this so that we can send this data to scrooge service in order to update
        // reference1 of refund
        $refundsData = [];

        foreach ($refunds as $refund)
        {
            $scroogeInputObject = (object)[];

            $paymentReference1 = $refund->payment->getReference1();

            $refundReference1  = $refund->getReference1();

            if (empty($refundReference1) === false)
            {
                // Do not update the refund reference1 as it is already present
                continue;
            }

            if (empty($paymentReference1) === false)
            {
                $refundId = $refund->getId();

                $scroogeInputObject->id          = $refundId;
                $scroogeInputObject->reference1  = $paymentReference1;

                $refundsData[] = $scroogeInputObject;
            }
            else
            {
                $this->trace->info(TraceCode::RECON_INFO,
                    [
                        'info_code'     => InfoCode::REFUND_AUTO_RECON_PAYMENT_REFERENCE_ID_EMPTY,
                        'refund_id'     => $refund->getId(),
                        'payment_id'    => $refund->payment->getId(),
                        'gateway'       => $refund->payment->getGateway()
                    ]);
            }

            // Empty allocated memory
            $scroogeInputObject = null;
        }

        if (empty($refundsData) === false)
        {
            $scroogeInputData = [];

            $scroogeInputData['refund_reference1'] = $refundsData;

            $response = $this->app['scrooge']->bulkUpdateRefundReference1($scroogeInputData);

            $this->trace->info(TraceCode::RECON_INFO,
                [
                    'info_code' => InfoCode::SCROOGE_RESPONSE_ON_REFERENCE1_UPDATE,
                    'response'  => $response
                ]);
        }
    }

    /**
     * This function calculates the sequence number of the refund associated with the payment. For example if a
     * payment p1 has 3 three refunds. The order in which the refunds were created would be its sequence number. For p1,
     * the refunds would be numbered as r1, r2, r3. This value is stored in reference3. The function calculates max of
     * reference 3 for all refunds associated with a payment and increments it by 1.
     *
     * @param $payment
     * @return int
     *
     */
    public static function getNewRefundSequenceNumberForPayment($payment)
    {
        $maxSeqNo = 0;

        $refunds = $payment->refunds;

        foreach($refunds as $refund)
        {
            $currentSeqNo = $refund->getReference3();

            if (($currentSeqNo !== null) and ($currentSeqNo > $maxSeqNo))
            {
                $maxSeqNo = $currentSeqNo;
            }
        }

        return $maxSeqNo + 1;
    }

    /**
     * Cancels all the payment refund request rows which are yet to be processed on the batch service
     * If the batch is yet to be processed / partially processed
     *
     * @param array $batch
     * @throws \RZP\Exception\BadRequestException
     */
    public function cancelRefundsBatch(array $batch)
    {
        (new Validator())->validateCancelRefundsBatch($batch);

        $batchService = new Batch\Service();

        $needToStopBatch = $batchService->isStoppingRequired($batch[Batch\Entity::STATUS]);

        if ($needToStopBatch === true)
        {
            $batchService->stopBatchProcess($batch);
        }
    }

    public function getNewProcessor($merchant)
    {
        return new Payment\Processor\Processor($merchant);
    }

    public function getRefundType(string $refundId, \RZP\Models\Merchant\Entity $merchant, $isBatch, $isScrooge) : string
    {
        $refundType = '';

        if (isset($isBatch) === true)
        {
            $refundType  = 'manual';
        }
        else if ((isset($isScrooge) === true) and ($isScrooge === true))
        {
            try
            {
                $scroogeData = $this->app['scrooge']->getRefund($refundId);

                $initiationtype = $scroogeData['body']['initiation_type'];

                $manualRefundType = 'Merchant Initiated';

                if (in_array($manualRefundType, $initiationtype) === true)
                {
                    $refundType = 'manual';
                }
                else
                {
                    $refundType = 'auto';
                }
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);
            }

        }

        return $refundType;
    }
}
