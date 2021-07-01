<?php

namespace RZP\Jobs;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\File;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;

class GatewayFile extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS    = 900;

    protected $gatewayFileId;

    protected $mode;

    /**
     * @var $gatewayFile File\Entity
     */
    protected $gatewayFile;

    // time (in seconds) after which the job is killed.
    // increasing the timeout to 3hrs as gateway file are having large data to process.
    public $timeout = 10800;

    public function __construct(string $gatewayFileId, string $mode)
    {
        parent::__construct();

        $this->gatewayFileId = $gatewayFileId;

        $this->mode = $mode;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->debug(TraceCode::GATEWAY_FILE_JOB_RECEIVED, [
                File\Entity::ID => $this->gatewayFileId
            ]);

            $gatewayFile = $this->repoManager
                                ->gateway_file
                                ->findOrFailPublic($this->gatewayFileId);

            $this->gatewayFile = $gatewayFile;

            $gatewayFileCore = new File\Core;

            $gatewayFileCore->process($gatewayFile);

            $this->retryFailedProcessing($gatewayFile);

            $this->trace->debug(TraceCode::GATEWAY_FILE_JOB_HANDLED, [
                File\Entity::ID => $this->gatewayFileId
            ]);
        }
        catch (\Throwable $e)
        {
            $this->handleException($e);
        }
    }

    /**
     * If a processing attempt fails, retry the processing based on the number of
     * attempts, else delete the job
     *
     * @param  File\Entity $gatewayFile entity to retry
     */
    protected function retryFailedProcessing(File\Entity $gatewayFile)
    {
        if ($gatewayFile->isFailed() === true)
        {
            if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();
            }
            else
            {
                $this->release(self::RELEASE_WAIT_SECS);
            }
        }
    }

    /**
     * If any unhandled exception occurs, retry the job if
     * max attempts is not exceeded.
     *
     * @param  \Throwable $e Exception durinng job processing
     */
    protected function handleException(\Throwable $e)
    {
        $this->trace->traceException(
            $e, Trace::ERROR, TraceCode::GATEWAY_FILE_JOB_ERROR, [File\Entity::ID => $this->gatewayFileId]);

        try
        {
            $this->resetGatewayFileState();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }

        if (($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS) or
            (($e instanceof BadRequestException) and
            ($e->getError()->getInternalErrorCode() !== ErrorCode::BAD_REQUEST_GATEWAY_FILE_ANOTHER_OPERATION_IN_PROGRESS)))
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);
        }
    }

    protected function resetGatewayFileState()
    {
        $this->gatewayFile->setProcessing(false);

        $this->repoManager->saveOrFail($this->gatewayFile);
    }

    protected function beforeJobKillCleanUp()
    {
        $this->resetGatewayFileState();

        parent::beforeJobKillCleanUp();
    }
}
