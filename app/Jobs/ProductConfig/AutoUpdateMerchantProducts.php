<?php

namespace RZP\Jobs\ProductConfig;

use App;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Services\Workflow;
use RZP\Models\Merchant\Product;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Core as DetailCore;

class AutoUpdateMerchantProducts extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $source;

    protected $merchantId;

    public function __construct(string $source, $merchantId)
    {
        parent::__construct();

        $this->source          = $source;
        $this->merchantId      = $merchantId;
    }

    public function handle()
    {
        parent::handle();

        $this->resetWorkflowSingleton();

        $this->trace->info(
            TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE_ATTEMPT,
            [
                'source'      => $this->source,
                'merchant_id' => $this->merchantId,
            ]
        );

        [$merchant, $merchantDetail] = (new DetailCore())->getMerchantAndDetailEntities($this->merchantId);

        try
        {
            (new Product\Core())->updateMerchantProductsIfApplicable($merchant, $merchantDetail);

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
                    'merchant_id' => $this->merchantId,
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
            $this->trace->error(TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE_ATTEMPT_MESSAGE_DELETE, [
                'source'       => $this->source,
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

    private function resetWorkflowSingleton()
    {
        $app = App::getFacadeRoot();
        $app['workflow'] =  new Workflow\Service($app);
    }
}
