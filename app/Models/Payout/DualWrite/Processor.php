<?php

namespace RZP\Models\Payout\DualWrite;

use App;
use Illuminate\Foundation\Application;

use RZP\Error\ErrorCode;
use RZP\Services\Mutex;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;

class Processor
{
    const MUTEX_LOCK_TIMEOUT_PS_DUAL_WRITE = 30;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->mutex = $this->app['api.mutex'];
    }

    public function dualWriteDataForPayoutId(string $payoutId)
    {
        $this->mutex->acquireAndRelease(
            'payout_dual_write_' . $payoutId,
            function() use ($payoutId) {
                $this->repo->transaction(function() use ($payoutId)
                {
                    (new Payout)->dualWritePSPayout($payoutId);

                    (new Reversal)->dualWritePSReversal($payoutId);

                    (new PayoutSource)->dualWritePSPayoutSources($payoutId);

                    (new PayoutDetails)->dualWritePSPayoutDetails($payoutId);

                    (new WorkflowEntityMap)->dualWritePSWorkflowEntityMap($payoutId);

                    (new PayoutStatusDetails)->dualWritePSPayoutStatusDetails($payoutId);

                    (new IdempotencyKey)->dualWritePSPayoutIdempotencyKey($payoutId);
                });
            },
            self::MUTEX_LOCK_TIMEOUT_PS_DUAL_WRITE,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }
}
