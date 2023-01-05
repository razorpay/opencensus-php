<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Product\Config\PaymentMethods;

class MerchantProductsConfig Extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantProductRequestId;

    protected $input;

    public function __construct(string $mode, string $merchantProductRequestId, $input)
    {
        parent::__construct($mode);

        $this->merchantProductRequestId = $merchantProductRequestId;

        $this->input = $input;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::MERCHANT_PRODUCT_CONFIG_REQUEST, [
                'merchantProductRequestId'  => $this->merchantProductRequestId,
                'input'                     => $this->input
            ]);

            $request = $this->repoManager->merchant_product_request->findOrFail($this->merchantProductRequestId);

            (new PaymentMethods())->create($this->input);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_PRODUCT_CONFIG_REQUEST_ERROR,
                [
                    'mode'        => $this->mode,
                    'merchant_product_request'  => $this->merchantProductRequestId,
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
            $this->trace->error(TraceCode::MERCHANT_PRODUCT_CONFIG_REQUEST_DELETE, [
                'id'           => $this->merchantProductRequestId,
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
