<?php

namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Partner;
use RZP\Models\Merchant;

class SendPartnerWeeklyActivationSummary extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 1;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $experimentId   = null;

    public    $timeout        = 1000;

    protected $partnerBatchIds;

    public function __construct(string $mode, array $partnerBatchIds)
    {
        parent::__construct($mode);

        $app = App::getFacadeRoot();

        $this->experimentId = $app['config']->get('app.send_weekly_activation_summary_to_partner_exp_id');

        $this->partnerBatchIds = $partnerBatchIds;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::WEEKLY_ACTIVATION_SUMMARY_REQUEST,
                [
                    'mode'        => $this->mode,
                    'partner_ids' => $this->partnerBatchIds,
                ]
            );

            $partnerCore = new Partner\Core();
            $merchantCore = new Merchant\Core();

            foreach ($this->partnerBatchIds as $partnerMerchantId)
            {
                $properties = [
                    'id'            => $partnerMerchantId,
                    'experiment_id' => $this->experimentId
                ];
                $isExpEnabled = $merchantCore->isSplitzExperimentEnable($properties, 'enable');

                if ($isExpEnabled === true)
                {
                    $partnerCore->sendPartnerWeeklyActivationSummaryEmails($partnerMerchantId);
                }
            }

            $this->delete();

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::WEEKLY_ACTIVATION_SUMMARY_JOB_ERROR,
                [
                    'mode'        => $this->mode,
                    'partner_ids' => $this->partnerBatchIds,
                ]
            );

            $this->checkRetry($e);
        }
    }


    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::WEEKLY_ACTIVATION_SUMMARY_QUEUE_DELETE, [
                'id'           => $this->partnerBatchIds,
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
