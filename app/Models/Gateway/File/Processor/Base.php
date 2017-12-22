<?php

namespace RZP\Models\Gateway\File\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Core;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File;
use RZP\Models\Gateway\File\Type;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;

/**
 * Base processor class defines the steps which need to be performed for processing
 * any gateway file entity. It defines abstact methods for each step which needs to be
 * implemented by child class
 */
abstract class Base extends Core
{
    /**
     * Mutex lock is acquired by default for 900s (15 minutes)
     */
    const MUTEX_LOCK_TIMEOUT = 900;

    protected $mutex;

    protected $gatewayFile;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Before starting the file generation, we acquire a mutex lock over the
     * gateway_file entity and check if it can be processed.This is to prevent
     * parallel requests from operating on the same gateway_file entity
     *
     * @param  File\Entity $gatewayFile
     */
    public function validateAndProcess(File\Entity $gatewayFile)
    {
        $this->gatewayFile = $gatewayFile;

        $this->mutex->acquireAndRelease(
            $this->gatewayFile->getId(),
            function ()
            {
                $this->gatewayFile->reload();

                $this->gatewayFile->getValidator()->validateIfProcessable();

                $this->gatewayFile->setProcessing(true);

                $this->repo->saveOrFail($this->gatewayFile);

                $this->process();
            },
            static::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_GATEWAY_FILE_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    /**
     * We perform the following steps to process the gateway_file entity
     * 1. Generate the required data
     * 2. Create the gateway_file entity
     * 3. Send the mail to gateway
     * Each of the steps needs to be implemented for respective child classes
     */
    protected function process()
    {
        try
        {
            $entities = $this->fetchEntities();

            $this->checkIfValidDataAvailable($entities);

            $data = $this->generateData($entities);

            $this->createFile($data);

            $this->sendFile($data);
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

        $this->gatewayFile->setProcessing(false);

        $this->repo->saveOrFail($this->gatewayFile);
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
     * If the processing fails due to some known reason like no data found for file
     * generation. In such cases, we mark the gateway_file entity as acknowledged.
     *
     * @param  string $code  Error code / reason for failure
     *
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

    abstract public function createFile($data);

    abstract public function sendFile($data);

    protected function getTpv()
    {
        $subType = $this->gatewayFile->getSubType();

        if ($subType === Type::TPV)
        {
            return true;
        }
        else if ($subType === Type::NON_TPV)
        {
            return false;
        }

        return null;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
