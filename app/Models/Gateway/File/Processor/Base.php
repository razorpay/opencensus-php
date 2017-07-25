<?php

namespace RZP\Models\Gateway\File\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Core;
use RZP\Models\Gateway\File;
use RZP\Models\Gateway\File\Status;

abstract class Base extends Core
{
    protected $gatewayFile;

    protected $data;

    public function __construct(File\Entity $gatewayFile)
    {
        parent::__construct();

        $this->gatewayFile = $gatewayFile;
    }

    public function process()
    {
        if ($this->canProcess() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_FILE_NON_RETRIABLE);
        }

        try
        {
            $this->generateData();

            $this->createFile();

            $this->sendMail();
        }
        catch (Exception\GatewayFileException $e)
        {
            $failureCode = $e->getFailureCode();

            $this->trace->traceException($e);

            $this->handleFileGenerationFailure($failureCode);
        }
        finally
        {
            $this->performPostProcessingTasks();
        }
    }

    protected function handleFileGenerationFailure(string $failureCode)
    {
        $this->gatewayFile->setStatus(Status::FAILED);

        $this->gatewayFile->setFailureCode($failureCode);

        $this->gatewayFile->setFailedAt(time());
    }

    protected function performPostProcessingTasks()
    {
        $this->gatewayFile->incrementAttempts();

        $this->repo->saveOrFail($this->gatewayFile);
    }

    abstract protected function canProcess(): bool;

    abstract public function generateData();

    abstract public function createFile();

    abstract public function sendMail();
}
