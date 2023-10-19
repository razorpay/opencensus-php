<?php

namespace RZP\Jobs;

use Monolog\Logger;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Upi\Turbo\Core;
use RZP\Models\Payment\Gateway;

class UpiTurboErrorMappingUpdater extends Job
{
    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 60;

    const MUTEX_RESOURCE = 'turbo_error_mappings';

    const MUTEX_LOCK_TIMEOUT = 3600;

    protected $queueConfigKey = 'turbo_upi_error_mapping_updater';

    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->mutex->acquireAndRelease(
                self::MUTEX_RESOURCE,
                function()
                {
                    $this->trace->info(TraceCode::UPI_TURBO_ERROR_MAPPING_UPDATE_JOB_INIT);

                    (new Core())->generateTurboErrorMappings([Gateway::UPI_AXISOLIVE]);

                    $this->trace->info(TraceCode::UPI_TURBO_ERROR_MAPPING_UPDATE_JOB_COMPLETE);

                    $this->delete();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                3,
            );
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Logger::ERROR,
                TraceCode::UPI_TURBO_ERROR_MAPPING_UPDATE_JOB_FAILED,
                [
                    'message' => $exception->getMessage()
                ]);

            if ($this->attempts() <= self::MAX_RETRY_ATTEMPT)
            {
                $this->release(self::MAX_RETRY_DELAY);
            }
            else
            {
                $this->trace->traceException(
                    $exception,
                    Logger::ERROR,
                    TraceCode::UPI_TURBO_ERROR_MAPPING_UPDATE_JOB_DELETED,
                    [
                        'attempts' => $this->attempts(),
                        'message'  => $exception->getMessage(),
                    ]);

                $this->delete();
            }
        }
    }
}
