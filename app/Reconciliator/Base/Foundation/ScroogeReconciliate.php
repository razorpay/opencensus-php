<?php

namespace RZP\Reconciliator\Base\Foundation;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Core;
use RZP\Jobs\ScroogeRefundRecon;
use Razorpay\Trace\Logger as Trace;

class ScroogeReconciliate extends Base\Core
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var array
     */
    protected $gatewayKeys = [];

    /**
     * @var string
     */
    protected $arn;

    /**
     * @var integer
     */
    protected $gatewaySettledAt;

    protected $reconciledAt;

    const ARN                   = 'arn';
    const REFUND_ID             = 'refund_id';
    const STATUS                = 'status';
    const GATEWAY_KEYS          = 'gateway_keys';
    const GATEWAY_SETTLED_AT    = 'gateway_settled_at';
    const RECONCILED_AT         = 'reconciled_at';

    const INFO_CODE                     = 'info_code';
    const MESSAGE                       = 'message';
    const MODE                          = 'mode';
    const SOURCE                        = 'source';
    const REFUNDS                       = 'refunds';
    const CHUNK_NUMBER                  = 'chunk_number';
    const REFUND_COUNT                  = 'refund_count';
    const BATCH_ID                      = 'batch_id';
    const RECON_GATEWAY                 = 'recon_gateway';
    const SHOULD_FORCE_UPDATE_ARN       = 'should_force_update_arn';

    const CHUNK_SIZE                    = 500;
    const TOTAL_REFUNDS                 = 'total_refunds';
    const TOTAL_CHUNKS                  = 'total_chunks';

    const FAILURE_COUNT                 = 'failure_count';

    const CURRENT_SUCCESS_COUNT         = 'current_success_count';
    const CURRENT_FAILURE_COUNT         = 'current_failure_count';
    const CURRENT_PROCESSED_COUNT       = 'current_processed_count';

    // Fields being used in batch recon request flow
    const RECON_VIA_BATCH_SERVICE       = 'recon_via_batch_service';
    const SHOULD_UPDATE_BATCH_SUMMARY   = 'should_update_batch_summary';

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return ScroogeReconciliate
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getArn()
    {
        return $this->arn;
    }

    /**
     * @param string $arn
     * @return ScroogeReconciliate
     */
    public function setArn(string $arn): self
    {
        $this->arn = $arn;

        return $this;
    }

    /**
     * @return int
     */
    public function getGatewaySettledAt()
    {
        return $this->gatewaySettledAt;
    }

    /**
     * @param int $gatewaySettledAt
     * @return ScroogeReconciliate
     */
    public function setGatewaySettledAt(int $gatewaySettledAt): self
    {
        $this->gatewaySettledAt = $gatewaySettledAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getReconciledAt()
    {
        return $this->reconciledAt;
    }

    /**
     * @param int $reconciledAt
     * @return ScroogeReconciliate
     */
    public function setReconciledAt(int $reconciledAt): self
    {
        $this->reconciledAt = $reconciledAt;

        return $this;
    }

    /**
     * @return array
     */
    public function getGatewayKeys(): array
    {
        return $this->gatewayKeys;
    }

    /**
     * @param array $gatewayKeys
     * @return ScroogeReconciliate
     */
    public function setGatewayKeys(array $gatewayKeys): self
    {
        $this->gatewayKeys = $gatewayKeys;

        return $this;
    }

    /**
     * @param array $scroogeReconciliate
     * @return array
     */
    public function toArray(array $scroogeReconciliate)
    {
        $scroogeReconData = [];

        foreach ($scroogeReconciliate as $refundId => $reconObject)
        {
            $scroogeReconData[] = [
                self::REFUND_ID             => $refundId,
                self::STATUS                => $reconObject->status,
                self::GATEWAY_KEYS          => $reconObject->gatewayKeys,
                self::ARN                   => $reconObject->arn,
                self::GATEWAY_SETTLED_AT    => $reconObject->gatewaySettledAt,
                self::RECONCILED_AT         => $reconObject->reconciledAt,
            ];
        }

        return $scroogeReconData;
    }

    /**
     * @param $data
     * @param $forceUpdateArn
     * @param Batch\Entity|null $batch
     * @param string $source Recon request source i.e mailgun, manual or lambda
     * @param string $gateway
     * @param string $batchId
     */
    public function callRefundReconcileFunctionOnScrooge($data,
                                                         $forceUpdateArn,
                                                         Batch\Entity $batch = null,
                                                         string $source,
                                                         string $gateway,
                                                         string $batchId)
    {
        //
        // We need to make smaller chunk and then dispatch,
        // else the queue might fail due to heavy payload.
        //
        $chunks = array_chunk($data, self::CHUNK_SIZE, true);

        $traceInfo = [
            self::TOTAL_REFUNDS => count($data),
            self::TOTAL_CHUNKS  => count($chunks),
            self::RECON_GATEWAY => $gateway,
            self::BATCH_ID      => $batchId,
        ];

        if ($batch === null)
        {
            $traceInfo[self::RECON_VIA_BATCH_SERVICE] = true;
        }
        else
        {
            $traceInfo[self::CURRENT_SUCCESS_COUNT]   = $batch->getSuccessCount();
            $traceInfo[self::CURRENT_FAILURE_COUNT]   = $batch->getFailureCount();
            $traceInfo[self::CURRENT_PROCESSED_COUNT] = $batch->getProcessedCount();
        }

        $this->trace->info(
            TraceCode::REFUND_RECON_QUEUE_SCROOGE_DISPATCH_METADATA, $traceInfo
        );

        foreach ($chunks as $key => $chunk)
        {
            $chunkData = [
                self::REFUNDS                       => $this->toArray($chunk),
                self::MODE                          => $this->mode,
                self::CHUNK_NUMBER                  => $key + 1,
                self::SHOULD_FORCE_UPDATE_ARN       => $forceUpdateArn,
                self::BATCH_ID                      => $batchId,
                self::SOURCE                        => $source,
                self::SHOULD_UPDATE_BATCH_SUMMARY   => $batch ? true : false,
            ];

            $traceData =[
                self::MODE                    => $this->mode,
                self::CHUNK_NUMBER            => $key + 1,
                self::REFUND_COUNT            => count($chunk),
                self::SHOULD_FORCE_UPDATE_ARN => $forceUpdateArn,
                self::BATCH_ID                => $batchId,
                self::SOURCE                  => $source,
                self::RECON_GATEWAY           => $gateway
            ];

            $this->trace->info(
                TraceCode::REFUND_RECON_QUEUE_SCROOGE_DISPATCH,
                $traceData
            );

            try
            {
                ScroogeRefundRecon::dispatch($chunkData);
            }
            catch (\Throwable $e)
            {
                // In Case of dispatch failure, Mark complete chunk as failed
                // Note : $batch object is null when recon request comes from
                // batch service. So need not update batch stats in such case.
                if ($batch !== null)
                {
                    (new Core)->updateScroogeBatchSummary($batch->getId(), 0, count($chunkData[self::REFUNDS]));
                }
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::REFUND_RECON_QUEUE_SCROOGE_DISPATCH_FAILED,
                    $traceData
                );
            }
        }
    }
}
