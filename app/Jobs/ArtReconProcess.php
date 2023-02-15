<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Refund;
use RZP\Reconciliator\Service;
use RZP\Models\Payment\Gateway;
use RZP\Base\RepositoryManager;
use RZP\Reconciliator\Validator;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\NetbankingSbi\SubReconciliator;

// ArtReconProcess job asyncly calls scrooge for refund
// updates and updates transaction entity for recon status
class ArtReconProcess extends Job
{
    protected $queueConfigKey = 'art_recon_entity_update';

    protected $mode;

    protected $data;

    const MAX_JOB_ATTEMPTS = 5;
    const JOB_RELEASE_WAIT = 300;
    const CHUNK_VALUE      = 1;

    const REFUNDS                     = 'refunds';
    const MODE                        = 'mode';
    const CHUNK_NUMBER                = 'chunk_number';
    const SHOULD_FORCE_UPDATE_ARN     = 'should_force_update_arn';
    const BATCH_ID                    = 'batch_id';
    const SOURCE                      = 'source';
    const SHOULD_UPDATE_BATCH_SUMMARY = 'should_update_batch_summary';

    // List of supported upi gateways with action refunds for entity update
    const SUPPORTED_UPI_GATEWAYS_ENTITY_UPDATE = [
        'upi_icici',
        'upi_mindgate',
        'upi_axis',
        'upi_juspay',
    ];

    public function __construct(array $payload)
    {
        $this->setMode($payload);

        parent::__construct($this->mode);

        $this->data = $payload;
    }

    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();

        $scrooge = $app['scrooge'];

        try
        {
            (new Validator)->validateUpdateRefundReconData($this->data);

            $scroogeReconData = [
                self::REFUNDS                   => $this->data['refunds'],
                self::MODE                      => $this->data['mode'],
                self::CHUNK_NUMBER              => self::CHUNK_VALUE,
                self::SHOULD_FORCE_UPDATE_ARN   => $this->data['should_force_update_arn'],
                self::BATCH_ID                  => null,
                self::SOURCE                    => $this->data['source'],
            ];

            $traceData = $scroogeReconData;

            unset($traceData[self::REFUNDS]);

            $this->trace->info(
                TraceCode::ART_REFUND_RECON_ENTITY_UPDATE_STARTED,
                [
                    'refund_data'    => $traceData,
                    'art_request_id' => $this->data['art_request_id'],
                    'gateway'        => $this->data['gateway'],
                ]
            );

            $scroogeReconData = $this->preprocessDataForRecon($scroogeReconData, $this->data['gateway']);

            if(empty($scroogeReconData) === true)
            {
                $this->trace->info(
                    TraceCode::ART_RECON_GATEWAY_VALIDATIONS_FAILED,
                    [
                        'refund_data'       => $traceData,
                        'art_request_id'    => $this->data['art_request_id'],
                        'gateway'           => $this->data['gateway'],
                    ]
                );
                return;
            }

            $response = $scrooge->initiateRefundRecon($scroogeReconData, true);

            if (isset($response['body']['response']) === true)
            {
                $this->trace->info(
                    TraceCode::ART_REFUND_RECON_SCROOGE_RESPONSE,
                    [
                        'failure_count'         => $response['body']['response']['failure_count'],
                        'art_request_id'        => $this->data['art_request_id'],
                        'gateway'               => $this->data['gateway'],
                    ]
                );
                (new Service)->reconcileRefundsAfterScroogeRecon($response['body']['response'], false);
            }

            if ((empty($this->data['upi']) === false) and
                (in_array($this->data['gateway'], self::SUPPORTED_UPI_GATEWAYS_ENTITY_UPDATE) === true))
            {
                (new Service())->updateUpiGatewayData($this->data['upi']);
            }

            $this->trace->info(
                TraceCode::ART_REFUND_RECON_ENTITY_UPDATE_SUCCESS,
                [
                    'refund_data'       => $traceData,
                    'art_request_id'    => $this->data['art_request_id'],
                    'gateway'           => $this->data['gateway'],
                ]
            );

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ART_REFUND_RECON_ENTITY_UPDATE_FAILED,
               [
                   'art_request_id'    => $this->data['art_request_id'],
                   'gateway'           => $this->data['gateway'],
               ]
            );

            $this->handleRefundJobRelease($this->data['art_request_id']);
        }
    }

    /**
     * Set mode for job.
     * @param $payload array
     *
     * @return void
     */
    protected function setMode(array $payload)
    {
        if (array_key_exists(self::MODE, $payload) === true)
        {
            $this->mode = $payload[self::MODE];
        }
    }

    /**
     * Handle retry for any exception while processing the job
     * @param int|null $chunkNumber
     */
    protected function handleRefundJobRelease(string $art_request_id)
    {
        try
        {
            $this->trace->info(
                TraceCode::ART_REFUND_RECON_UPDATE_RETRY,
                [
                    'art_request_id' => $art_request_id,
                    'attempts'       => $this->attempts(),
                ]
            );

            if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
            {
                $this->trace->error(
                    TraceCode::ART_REFUND_RECON_UPDATE_FAILED_AFTER_RETRIES,
                    [
                        'job_attempts'   => $this->attempts(),
                        'art_request_id' => $art_request_id,
                        'message'        => 'Deleting the job after configured number of tries. Still unsuccessful.'
                    ]);

                $this->delete();

            }
            else
            {
                $this->release(self::JOB_RELEASE_WAIT);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ART_REFUND_RECON_UPDATE_RETRY_FAILED);

            $this->delete();
        }
    }

    private function preprocessDataForRecon(array $scroogeReconData, string $gateway): array
    {
        $refundsSize = count($scroogeReconData['refunds']);

        if($gateway == Gateway::NETBANKING_SBI)
        {
            for ($i=0; $i<$refundsSize; $i++) {
                $scroogeReconData['refunds'][$i] = (new SubReconciliator\RefundReconciliate())->handleGateway($scroogeReconData['refunds'][$i]);
            }
        }

        $scroogeReconData = handleStatusWithArn($scroogeReconData, $gateway);

        for ($i=0; $i<$refundsSize; $i++) {
            if(isset($scroogeReconData['refunds'][$i]['payment_id']))
            {
                unset($scroogeReconData['refunds'][$i]['payment_id']);
            }
        }
        return $scroogeReconData;
    }
}

function handleStatusWithArn(array $scroogeReconData, string $gateway): array
{
    $refundsSize = count($scroogeReconData['refunds']);

    if($gateway == Gateway::NETBANKING_ICICI)
    {
        for ($i=0; $i<$refundsSize; $i++) {
            if(is_null($scroogeReconData['refunds'][$i]['arn']) == false)
            {
                $scroogeReconData['refunds'][$i]['status'] = Refund\Status::PROCESSED;
            }
            else
            {
                $scroogeReconData['refunds'][$i]['status'] = null;
            }
        }
    }
    return $scroogeReconData;
}
