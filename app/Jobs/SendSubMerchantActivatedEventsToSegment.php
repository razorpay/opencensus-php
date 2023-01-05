<?php

namespace RZP\Jobs;

use App;
use Error;
use RZP\Constants\Mode;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail\Status;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class SendSubMerchantActivatedEventsToSegment extends Job
{
    const MAX_RETRY_ATTEMPT = 2;

    const RETRY_INTERVAL = 300;

    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantId;

    protected $currentActivationStatus;

    public function __construct(string $merchantId, string $currentActivationStatus)
    {
        parent::__construct(Mode::LIVE);

        $this->merchantId = $merchantId;

        $this->currentActivationStatus = $currentActivationStatus;
    }

    /**
     * Sends segment events to each affiliated
     */
    public function handle()
    {
        parent::handle();

        $merchantCore = new Merchant\Core();
        $app          = App::getFacadeRoot();

        try
        {
            if (in_array($this->currentActivationStatus, [Status::INSTANTLY_ACTIVATED, Status::ACTIVATED, Status::ACTIVATED_MCC_PENDING]))
            {
                $partners = $merchantCore->fetchAffiliatedPartners($this->merchantId);
                foreach ($partners as $partner)
                {
                    $properties = [
                        'partner_id'  => $partner->getId(),
                        'merchant_id' => $this->merchantId,
                    ];

                    $this->trace->info(TraceCode::SEND_SUBMERCHANT_ACTIVATED_EVENTS_JOB, $properties);

                    $count_of_activated_subm = $this->getCountOfActivatedSubmerchants($partner->getId());

                    $properties += [
                        'count_of_activated_subm' => $count_of_activated_subm
                    ];

                    $app['segment-analytics']->pushIdentifyAndTrackEvent(
                        $partner, $properties, SegmentEvent::SUBMERCHANT_ACTIVATED);
                }
                // Fire all pushed events in a single request.
                $app['segment-analytics']->buildRequestAndSend();
            }

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SEND_SUBMERCHANT_ACTIVATED_EVENTS_JOB_ERROR,
                [
                    'merchant_id' => $this->merchantId,
                ]);
            $this->checkRetry($e);
        }

    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::SEND_SUBMERCHANT_ACTIVATED_EVENTS_JOB_DELETE, [
                'merchant_id'  => $this->merchantId,
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

    protected function getCountOfActivatedSubmerchants($partnerId)
    {
        $submerchantIds = $this->repoManager->merchant_detail->getSubmerchantIdsByActivationStatus($partnerId,
                                                                                                   [Status::INSTANTLY_ACTIVATED,
                                                                                                    Status::ACTIVATED,
                                                                                                    Status::ACTIVATED_MCC_PENDING]);

        return count($submerchantIds);
    }

}
