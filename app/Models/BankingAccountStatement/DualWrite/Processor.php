<?php

namespace RZP\Models\BankingAccountStatement\DualWrite;

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
    const MUTEX_LOCK_TIMEOUT_PS_DUAL_WRITE = 30;

    const MUTEX_LOCK_TIMEOUT_ACCOUNT_STATEMENT_DUAL_WRITE = 30;

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

    public function dualWriteDataForBasId(string $basId)
    {
        /** @var Entity $bas */
        $bas = $this->mutex->acquireAndRelease(
            'bas_dual_write_' . $basId,
            function() use ($basId) {
                return $this->repo->transaction(function() use ($basId)
                {
                    $bas = (new BankingAccountStatement)->dualWritePSBas($basId);

                    return $bas;
                });
            },
            self::MUTEX_LOCK_TIMEOUT_PS_DUAL_WRITE,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        if ($bas->transaction != null)
        {
            $this->app->events->dispatch('api.transaction.created', $bas->transaction);
        }
    }

    public function dualWriteAccountStatementServiceData($input)
    {
        $basId = $input['id'];
        /** @var Entity $bas */
        $bas = $this->mutex->acquireAndRelease(
            'account_statement_dual_write_' . $basId,
            function() use ($input, $basId) {
                return $this->repo->transaction(function() use ($input, $basId)
                {
                    if ($this->isDuplicateRecord($input))
                    {
                        $this->trace->info(
                            TraceCode::BAS_DUAL_WRITE_SKIPPING_DUPLICATE_RECORD,
                            ['bas_id' => $basId]
                        );

                        return null;
                    }

                    $bas = (new BankingAccountStatement)->dualWriteAccountStatementBas($input);

                    return $bas;
                });
            },
            self::MUTEX_LOCK_TIMEOUT_ACCOUNT_STATEMENT_DUAL_WRITE,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        if ($bas !== null && $bas->transaction != null)
        {
            $this->app->events->dispatch('api.transaction.created', $bas->transaction);
        }
    }

    // Function to find duplicate record from the db
    private function isDuplicateRecord($basInput)
    {
        $existingBAS = $this->repo->banking_account_statement->getExistingUniqueRecord(
            $basInput[Entity::BANK_TRANSACTION_ID],
            $basInput[Entity::ACCOUNT_NUMBER],
            $basInput[Entity::POSTED_DATE],
            $basInput[Entity::AMOUNT],
            $basInput[Entity::TYPE],
            $basInput[Entity::CHANNEL],
            $basInput[Entity::BANK_SERIAL_NUMBER]
        );

        // Duplicate if record exists and it has a different id
        // For input with same id, we can have updates in multiple dual write calls
        return $existingBAS !== null && $existingBAS->getId() != $basInput[Entity::ID];
    }
}
