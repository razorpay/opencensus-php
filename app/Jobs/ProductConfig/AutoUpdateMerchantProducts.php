<?php

namespace RZP\Jobs\ProductConfig;

use App;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Services\Workflow;
use RZP\Models\Merchant\Product;
use Razorpay\Trace\Logger as Trace;

class AutoUpdateMerchantProducts extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $source;

    protected $merchant;

    protected $merchantDetails;

    public function __construct(string $source, $merchant, $merchantDetails)
    {
        parent::__construct();

        $this->source          = $source;
        $this->merchant        = $merchant;
        $this->merchantDetails = $merchantDetails;
    }

    public function handle()
    {
        parent::handle();

        $this->resetWorkflowSingleton();

        $this->trace->info(
            TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE_ATTEMPT,
            [
                'source'      => $this->source,
                'merchant_id' => $this->merchant->getId(),
            ]
        );

        try
        {
            (new Product\Core())->updateMerchantProductsIfApplicable($this->merchant, $this->merchantDetails);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE_ATTEMPT_FAILED,
                [
                    'source'      => $this->source,
                    'merchant_id' => $this->merchant->getId(),
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE_ATTEMPT_MESSAGE_DELETE, [
                'source'       => $this->source,
                'merchant_id'  => $this->merchant->getId(),
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

    private function resetWorkflowSingleton()
    {
        $app = App::getFacadeRoot();
        $app['workflow'] =  new Workflow\Service($app);
    }
}
