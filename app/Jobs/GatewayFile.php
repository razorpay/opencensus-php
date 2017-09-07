<?php

namespace RZP\Jobs;

use RZP\Exception\BadRequestException;
use RZP\Models\Gateway\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class GatewayFile extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    const MAX_ALLOWED_ATTEMPTS = 2;
    const RELEASE_WAIT_SECS    = 10;

    protected $gatewayFileId;

    protected $mode;

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
            $gatewayFile = $this->repoManager
                                ->gateway_file
                                ->findOrFailPublic($this->gatewayFileId);

            $gatewayFileCore = new File\Core;

            $gatewayFileCore->process($gatewayFile);

            $gatewayFile->reload();

            $this->retryFailedProcessing($gatewayFile);
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
        if (($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS) or
            ($e instanceof BadRequestException))
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);
        }
    }
}
