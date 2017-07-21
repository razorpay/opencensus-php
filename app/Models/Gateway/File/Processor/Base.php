<?php

namespace RZP\Models\Gateway\File\Processor;

use RZP\Exception;
use RZP\Models\Base\Core;
use RZP\Models\Gateway\File;
use RZP\Models\Gateway\File\Status;

class Base extends Core
{
    protected $gatewayFile;

    public function __construct(File\Entity $gatewayFile)
    {
        parent::__construct();

        $this->gatewayFile = $gatewayFile;
    }

    public function process()
    {
        if ($this->canProcess() === false)
        {
            return;
        }

        try
        {
            $fileData = $this->generateFileData();

            $this->createFile($fileData);

            $this->sendMail();
        }
        catch (Exception\GatewayFileException $e)
        {
            $failureCode = $e->getMessage();

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
        $this->repo->saveOrFail($this->gatewayFile);
    }
}
