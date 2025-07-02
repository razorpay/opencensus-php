<?php

namespace RZP\Models\FundAccount\DualWrite;

use App;
use RZP\Trace\TraceCode;
use Illuminate\Foundation\Application;

use RZP\Error\ErrorCode;
use RZP\Services\Mutex;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccountStatement\Entity;

class Processor
{
    const MUTEX_LOCK_TIMEOUT_FUND_ACCOUNT_DUAL_WRITE = 30;

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

    public function dualWriteRxFundAccount($input)
    {
        $fundAccountId = $input['id'];

        $this->mutex->acquireAndRelease(
            'rx_fund_account_dual_write_' . $fundAccountId,
            function() use ($input, $fundAccountId) {
                return $this->repo->transaction(function() use ($input, $fundAccountId)
                {
                    $fundAccount = (new FundAccount)->dualWriteRxFundAccount($input);

                    return $fundAccount;
                });
            },
            self::MUTEX_LOCK_TIMEOUT_FUND_ACCOUNT_DUAL_WRITE,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }
}
