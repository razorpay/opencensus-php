<?php

namespace RZP\Models\Gateway\File\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Core;
use RZP\Models\Gateway\File;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;

/**
 * Base processor class defines the steps which need to be performed for processing
 * any gateway file entity. It defines abstact methods for each step which needs to be
 * implemented by child class
 */
abstract class Base extends Core
{
    protected $gatewayFile;

    protected $data = [];

    /**
     * We perform the following steps to process the gateway_file entity
     * 1. Generate the required data
     * 2. Create the gateway_file entity
     * 3. Send the mail to gateway
     * Each of the steps needs to be implemented for respective child classes
     */
    public function process(File\Entity $gatewayFile)
    {
        $this->gatewayFile = $gatewayFile;
        // We check if the gateway file entity is at a state where it can be processed
        // again
        if ($this->canRetry() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_FILE_NON_RETRIABLE);
        }

        try
        {
            $entites = $this->fetchEntities();

            $this->checkIfValidDataAvailable($entites);

            $this->generateData($entites);

            $this->createFile();

            $this->sendMail();
        }
        catch (Exception\GatewayFileException $e)
        {
            $this->handleProcessingFailure($e);
        }
        finally
        {
            $this->performPostProcessingTasks();
        }
    }

    /**
     * Handles any exception thrown during processing. Here we update the status as failed
     * with appropriate failure_code.
     *
     * @param  Exception\GatewayFileException $e Exception object
     */
    protected function handleProcessingFailure(Exception\GatewayFileException $e)
    {
        $failureCode = $e->getFailureCode();

        $this->trace->traceException($e);

        $this->gatewayFile->setStatus(Status::FAILED);

        $this->gatewayFile->setFailureCode($failureCode);

        $this->gatewayFile->setFailedAt(time());
    }

    /**
     * At this stage we finally save the updated gateway_file entity to the database
     */
    protected function performPostProcessingTasks()
    {
        $this->gatewayFile->incrementAttempts();

        $this->repo->saveOrFail($this->gatewayFile);
    }

    abstract protected function canRetry(): bool;

    abstract public function fetchEntities(): PublicCollection;

    abstract public function checkIfValidDataAvailable(PublicCollection $entites);

    abstract public function generateData(PublicCollection $entites): array;

    abstract public function createFile();

    abstract public function sendMail();
}
