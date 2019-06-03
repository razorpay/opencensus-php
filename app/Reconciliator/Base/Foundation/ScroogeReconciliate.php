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

    const ARN                   = 'arn';
    const REFUND_ID             = 'refund_id';
    const STATUS                = 'status';
    const GATEWAY_KEYS          = 'gateway_keys';
    const GATEWAY_SETTLED_AT    = 'gateway_settled_at';

    const INFO_CODE                 = 'info_code';
    const MESSAGE                   = 'message';
    const MODE                      = 'mode';
    const SOURCE                    = 'source';
    const REFUNDS                   = 'refunds';
    const CHUNK_NUMBER              = 'chunk_number';
    const BATCH_ID                  = 'batch_id';
    const RECON_GATEWAY             = 'recon_gateway';
    const SHOULD_FORCE_UPDATE_ARN   = 'should_force_update_arn';

    const CHUNK_SIZE                = 500;
    const TOTAL_CHUNKS              = 'total_chunks';

    const FAILURE_COUNT             = 'failure_count';

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
                self::GATEWAY_SETTLED_AT    => $reconObject->gatewaySettledAt
            ];
        }

        return $scroogeReconData;
    }

    /**
     * @param $data
     * @param $forceUpdateArn
     * @param Batch\Entity $batch
     * @param $source String Recon request source i.e mailgun, manual or lamba
     */
    public function callRefundReconcileFunctionOnScrooge($data, $forceUpdateArn, Batch\Entity $batch, string $source)
    {
        //
        // We need to make smaller chunk and then dispatch,
        // else the queue might fail due to heavy payload.
        //
        $chunks = array_chunk($data, self::CHUNK_SIZE, true);

        foreach ($chunks as $key => $chunk)
        {
            $chunkData = [
                self::REFUNDS                 => $this->toArray($chunk),
                self::MODE                    => $this->mode,
                self::CHUNK_NUMBER            => $key + 1,
                self::SHOULD_FORCE_UPDATE_ARN => $forceUpdateArn,
                self::BATCH_ID                => $batch->getId(),
                self::SOURCE                  => $source
            ];

            $traceData =[
                self::MODE                    => $this->mode,
                self::CHUNK_NUMBER            => $key + 1,
                self::SHOULD_FORCE_UPDATE_ARN => $forceUpdateArn,
                self::BATCH_ID                => $batch->getId(),
                self::SOURCE                  => $source,
                self::RECON_GATEWAY           => $batch->getGateway(),
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
                // Mark complete chunk as failed
                (new Core)->updateScroogeBatchSummary($batch->getId(), 0, count($chunkData[self::REFUNDS]));

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
