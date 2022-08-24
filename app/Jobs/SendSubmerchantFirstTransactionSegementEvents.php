<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Services\Segment\EventCode;
use Razorpay\Trace\Logger as Trace;

class SendSubmerchantFirstTransactionSegementEvents extends Job
{
    const RETRY_INTERVAL      = 300;

    const MAX_RETRY_ATTEMPT   = 1;

    protected $experimentId   = null;

    public    $timeout        = 1000;

    protected $batchIds;

    public function __construct(string $mode, array $batchIds)
    {
        parent::__construct($mode);

        $this->experimentId = app('config')->get('app.send_submerchant_first_transaction_segment_event');

        $this->batchIds = $batchIds;
    }

    /**
     * Sends segment events to each affiliated
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::SUBMERCHANT_FIRST_TRANSACTION_DETAILS_CRON_TRACE,
                [
                    'mode'        => $this->mode,
                    'submerchants' => $this->batchIds,
                ]
            );

            $merchantCore = new Merchant\Core();

            foreach ($this->batchIds as $merchantId)
            {
                $properties = [
                    'id'            => $merchantId,
                    'experiment_id' => $this->experimentId
                ];
                $isExpEnabled = $merchantCore->isSplitzExperimentEnable($properties, 'enable');

                if ($isExpEnabled === true)
                {
                    $partners = $merchantCore->fetchAffiliatedPartners($merchantId);

                    foreach ($partners as $partner)
                    {
                        $segmentProperties = [
                            'merchantId' => $merchantId,
                            'partnerId'  => $partner->getId(),
                        ];

                        $this->trace->info(TraceCode::SUBMERCHANT_FIRST_TRANSACTION_DETAILS_CRON_TRACE, [
                            'type'                  => 'submerchant_first_transaction_cron',
                            'merchant_id'           => $merchantId,
                            'segment_properties'    => $segmentProperties
                        ]);

                        app('segment-analytics')->pushIdentifyAndTrackEvent($partner, $segmentProperties, EventCode::SUBMERCHANT_FIRST_TRANSACTION);
                    }
                }
            }

            app('segment-analytics')->buildRequestAndSend();

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SUBMERCHANT_FIRST_TRANSACTION_SEGMENT_JOB_ERROR,
                [
                    'mode'        => $this->mode,
                    'partner_ids' => $this->batchIds,
                ]
            );

            $this->checkRetry();
        }
    }


    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::SUBMERCHANT_FIRST_TRANSACTION_SEGMENT_QUEUE_DELETE, [
                'id'           => $this->batchIds,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
