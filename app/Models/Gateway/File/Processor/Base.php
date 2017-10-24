<?php

namespace RZP\Models\Gateway\File\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
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

        $this->checkIfRetriable();

        try
        {
            $entites = $this->fetchEntities();

            $this->checkIfValidDataAvailable($entites);

            $this->generateData($entites);

            $this->createFile();

            $this->sendFile();
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

    public function acknowledge(File\Entity $gatewayFile, $data)
    {
        $gatewayFile->getValidator()->validateInput('acknowledge', $data);

        $gatewayFile->setStatus(Status::ACKNOWLEDGED);

        $gatewayFile->setAcknowledgedAt(time());

        $gatewayFile->fill($data);

        $this->repo->saveOrFail($gatewayFile);
    }

    public function setGatewayFile(File\Entity $gatewayFile)
    {
        $this->gatewayFile = $gatewayFile;

        return $this;
    }

    /**
     * Handles any exception thrown during processing. Here we update the status as failed
     * with appropriate failure_code.
     *
     * @param  Exception\GatewayFileException $e Exception object
     */
    protected function handleProcessingFailure(Exception\GatewayFileException $e)
    {
        throw $e;


        $this->trace->traceException($e);

        if ($this->shouldNotReportFailure($e->getCode()) === true)
        {
            $this->acknowledge($this->gatewayFile, [
                File\Entity::COMMENTS => $e->getMessage(),
            ]);

            return;
        }

        $this->gatewayFile->setStatus(Status::FAILED);

        $this->gatewayFile->setErrorCode($e->getErrorCode());

        $this->gatewayFile->setErrorDescription($e->getMessage());

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

    /**
     * If it is a retry attempt for an existing gateway file, we check if it is
     * in a valid state to be reried depending on the type of the gateway file
     */
    protected function checkIfRetriable()
    {
        if ($this->canRetry() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_FILE_NON_RETRIABLE);
        }
    }

    protected function isFileGenerated(): bool
    {
        if ($this->gatewayFile->isFileGenerated() === true)
        {
            $file = $this->gatewayFile
                               ->files()
                               ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                               ->first();

            return $file !== null;
        }

        return false;
    }

    /**
     * Checks if the given gateway file can be retried or not. Currently
     * we consider that if the refund gateway_file entity is in acknowledged state
     * then it cannot be retried further.
     *
     * @return bool Whether gateway_file entity can be processed again or not
     */
    protected function canRetry(): bool
    {
        return ($this->gatewayFile->isAcknowledged() !== true);
    }

    /**
     * If the processing fails due to some known reason like no data found for file
     * generation. In such cases, we mark the gateway_file entity as acknowledged
     * @param  string   Error code / reason for failure
     * @return bool
     */
    abstract protected function shouldNotReportFailure(string $code): bool;

    abstract public function fetchEntities(): PublicCollection;

    /**
     * Checks if the entity data fetched is not empty and satisfies the criteria
     * for generating the file
     * @param  PublicCollection $entites Entities required to generate the file
     */
    abstract public function checkIfValidDataAvailable(PublicCollection $entites);

    abstract public function generateData(PublicCollection $entites);

    abstract public function createFile();

    abstract public function sendFile();
}
