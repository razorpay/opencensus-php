<?php

namespace RZP\Jobs;

use App;

use RZP\Constants\Metric;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Service as PaymentService;

class MerchantBalanceUpdate extends Job
{
    const MUTEX_LOCK_TIMEOUT = 3600; // sec

    const RELEASE_WAIT_SECS    = 300;

    /**
     * @var string
     */
    protected $queueConfigKey = 'merchant_balance_update';

    /**
     * @var array
     */
    protected $input;

    protected $asyncBalancePushedAt;


    public function __construct(array $input, string $mode = null, int $asyncBalancePushedAt = 0)
    {
        parent::__construct($input['mode']);

        $this->input = $input;

        $this->asyncBalancePushedAt = $asyncBalancePushedAt;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $this->mutex = App::getFacadeRoot()['api.mutex'];

            $key = md5(json_encode($this->input)); // nosemgrep :  php.lang.security.weak-crypto.weak-crypto

            $this->mutex->acquireAndRelease(
                $key,
                function ()
                {
                    (new PaymentService)->updateMerchantBalance($this->input['payment_id']);

                     $this->trace->info(
                        TraceCode::MERCHANT_BALANCE_UPDATE_SUCCESSFULL,[
                        'input'          => $this->input
                    ]);

                    $this->delete();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ASYNC_MERCHANT_BALANCE_UPDATE_IN_PROGRESS);

            if ($this->asyncBalancePushedAt !== 0)
            {
                $this->trace->histogram(Metric::ASYNC_TRANSACTION_DURATION_SECONDS, time() - $this->asyncBalancePushedAt);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::MERCHANT_BALANCE_UPDATE_FAILURE,
                $this->input);

            $this->release(self::RELEASE_WAIT_SECS);
        }
    }
}
