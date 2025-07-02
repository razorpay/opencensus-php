<?php

namespace RZP\Models\Contact\DualWrite;

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
    const MUTEX_LOCK_TIMEOUT_CONTACT_DUAL_WRITE = 30;

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

    public function dualWriteRxContact($input)
    {
        $contactId = $input['id'];

        $this->mutex->acquireAndRelease(
            'rx_contact_dual_write_' . $contactId,
            function() use ($input, $contactId) {
                return $this->repo->transaction(function() use ($input, $contactId)
                {
                    $contact = (new Contact)->dualWriteRxContact($input);

                    return $contact;
                });
            },
            self::MUTEX_LOCK_TIMEOUT_CONTACT_DUAL_WRITE,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }
}
