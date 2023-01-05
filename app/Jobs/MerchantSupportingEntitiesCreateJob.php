<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Services\Workflow;
use RZP\Models\Merchant\Metric;
use Razorpay\Trace\Logger as Trace;
use Jitendra\Lqext\TransactionAware;

class MerchantSupportingEntitiesCreateJob extends Job
{
    use TransactionAware;

    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantId;

    protected $partnerId;


    public function __construct($mode, string $merchantId, $partnerId)
    {
        parent::__construct($mode);
        $this->merchantId = $merchantId;
        $this->partnerId  = $partnerId;
    }

    public function handle()
    {
        parent::handle();

        $this->resetWorkflowSingleton();

        $this->trace->info(
            TraceCode::MERCHANT_SUPPORTING_ENTITIES_ASYNC_JOB,
            [
                'merchant_id' => $this->merchantId,
                'partner_id'  => $this->partnerId
            ]
        );

        try
        {
            $merchant = $this->repoManager->merchant->findOrFailPublic($this->merchantId);
            $partner  = $this->repoManager->merchant->findOrFailPublic($this->partnerId);
            $merchantCore =  (new Merchant\Core());
            $merchantCore->addMerchantSupportingEntitiesAsync($merchant, $partner);
            $merchantCore->addSubmerchantOnboardingV2Feature($merchant);
            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_SUPPORTING_ENTITIES_ASYNC_JOB_FAILED,
                [
                    'merchant_id' => $this->merchantId,
                    'partner_id'  => $this->partnerId,
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
            $this->trace->error(TraceCode::MERCHANT_SUPPORTING_ENTITIES_ASYNC_JOB_MESSAGE_DELETE, [
                'merchant_id'  => $this->merchantId,
                'partner_id'   => $this->partnerId,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->trace->count(Metric::MERCHANT_SUPPORT_ENTITIES_CREATION_FAILURE_TOTAL, []);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    private function resetWorkflowSingleton()
    {
        $app = App::getFacadeRoot();
        $app['workflow'] =  new Workflow\Service($app);
    }
}
