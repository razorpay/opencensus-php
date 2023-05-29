<?php

namespace RZP\Models\Gateway\File\Processor;

use App;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Core;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File;
use RZP\Base\RuntimeManager;
use RZP\Models\Payment\Refund;
use RZP\Models\Gateway\File\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Constants;
use RZP\Jobs\FileGenerationInstrumentation;


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

    const NBPLUS_FETCH_MAX_ATTEMPTS = 2;

    /**
     * Being used to paginate the fetch from nbplus
     * Number of gateway entities to be fetched in each call
     */
    const NBPLUS_FETCH_ENTITY_COUNT = 1000;

    /**
     * Being used to paginate the fetch from UPS
     * Number of gateway entities to be fetched in each call
     */
    const UPS_FETCH_ENTITY_COUNT = 1000;

    protected $mutex;

    /**
     * @var $gatewayFile File\Entity
     */
    protected $gatewayFile;
    protected $refundCore;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
        $this->refundCore = new Refund\Core;

        $this->increaseAllowedSystemLimits();
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
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
            // Resetting any global variables being used since this is a singleton class
            $this->resetFileProcessorAttributes();

            $entities = $this->repo->useSlave(function ()
            {
                return $this->fetchEntities();
            });

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

    public function resetFileProcessorAttributes()
    {
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

            return ($file !== null);
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

    /**
     * This function is marking all the netbanking refunds sent in gateway file as reconciled.
     * As we are sending refunds to bank and there is no acknowledgment of actual processing from bank side,
     * after sending file we mark them as reconciled, assuming refunds are processed at bank side.
     *
     * @param $data
     */
    protected function reconcileNetbankingRefunds(array $data)
    {
        $this->refundCore->reconcileNetbankingRefunds($data);
    }

    protected function fetchNbPlusGatewayEntities($paymentIds, $entity)
    {
        $shouldFetchEntities = true;

        $start = 0;

        $fetchLimit = self::NBPLUS_FETCH_ENTITY_COUNT;

        $gatewayData = [];

        $fetchSuccess = true;

        while ($shouldFetchEntities === true)
        {
            $requestPaymentIds = array_slice($paymentIds, $start, $fetchLimit);

            if ((count($requestPaymentIds) === 0) or ($fetchSuccess === false))
            {
                $shouldFetchEntities = false;
            }
            else
            {
                for ($i = 0; $i < self::NBPLUS_FETCH_MAX_ATTEMPTS; $i++)
                {
                    try
                    {
                        $request = [
                            'payment_ids'   => $requestPaymentIds,
                        ];

                        $response = App::getFacadeRoot()['nbplus.payments']->fetchNbPlusData($request, $entity);

                        $start += $fetchLimit;

                        $gatewayData = array_merge($gatewayData, $response['items']);

                        $fetchSuccess = true;

                        break;
                    }
                    catch (\Exception $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::GATEWAY_FILE_ERROR_GENERATING_DATA,
                            [
                                'input' => $request,
                                'id'    => $this->gatewayFile->getId(),
                            ]
                        );

                        $fetchSuccess = false;
                    }
                }
            }
        }

        return [$gatewayData, $fetchSuccess];
    }

    /**
     * @throws Exception\GatewayFileException
     */
    protected function sendBeamRequest(array $data, array $interval, array $mailInfo, bool $synchronous)
    {
        $beamResponse = $this->app['beam']->beamPush($data, $interval, $mailInfo, $synchronous);

        if ((isset($beamResponse['success']) === false) or
            ($beamResponse['success'] === null) or
            ($beamResponse['failed'] !== null))
        {
            $this->trace->info(
                TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
            
            throw new Exception\GatewayFileException(ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'beam_response' => $beamResponse,
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'target'        => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }

        $this->gatewayFile->setStatus(Status::FILE_SENT);

        $this->gatewayFile->setFileSentAt(time());
    }

    protected function fetchFilestoreIds()
    {
        $fileStoreEntities = $this->repo->file_store->getFilesBasedOnEntity($this->gatewayFile->getId());

        $fileStoreIds = [];

        foreach ($fileStoreEntities as $fileStoreEntity)
        {
            $fileStoreIds[] = $fileStoreEntity["id"];
        }

        return $fileStoreIds;
    }

    protected function getFileNames($files)
    {
        $fileInfo = [];

        foreach ($files as $file)
        {
            $fullFileName = $file->getName() . '.' . $file->getExtension();

            $fileInfo[] = $fullFileName;
        }

        return $fileInfo;
    }

    protected function getSingleFileName($file)
    {
        return $file->getName() . '.' . $file->getExtension();
    }

    protected function setFilesBeamStatus($fileList, $fileStatus)
    {
        foreach ($fileList as $eachFile)
        {
            $eachFile->setComments($fileStatus);

            $this->repo->saveOrFail($eachFile);
        }
    }

    protected function filterFiles(& $fileList, & $statusFiles, $fileStatus)
    {
        foreach ($fileList as $index => $eachFile)
        {
            if ($eachFile->getComments() === $fileStatus)
            {
                array_push($statusFiles, $this->getSingleFileName($eachFile));

                unset($fileList[$index]);
            }
        }
    }

    /**
     * Process the file_generation_instrumentation entity via queue
     *
     * @param string $gatewayFileId
     * @param string|null $gateway
     */
    protected function fileGenerationProcessAsync(string $gatewayFileId, string $gateway = null)
    {
        try
        {
            $variant = $this->app['razorx']->getTreatment(
                $gateway,
                'emandate_file_generation_instrumentation',
                $this->mode
            );

            if (strtolower($variant) !== 'on')
            {
                return;
            }

            $files = $this->repo->file_store->getFilesBasedOnEntity($gatewayFileId);

            foreach ($files as $file)
            {
                if($file["type"] === "citi_nach_debit_summary")
                {
                    continue;
                }

                $this->trace->info(TraceCode::FILE_GENERATE_RAZORX,
                    [
                        "file_id"    => $file["id"],
                        "gateway_id" => $gatewayFileId,
                        "gateway"    => $gateway
                    ]);

                FileGenerationInstrumentation::dispatch($gatewayFileId, $file["id"], $this->mode);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->error(TraceCode::FILE_INSTRUMENTATION_DISPATCH_ERROR,
                                [
                                    File\Entity::ID => $gatewayFileId,
                                    "error" => $ex
                                ]);
        }
    }

    public function generateMetric(string $metricName, array $metricDimensions=[])
    {
        try {
            $this->trace->count($metricName, $this->getMetricDimensions($metricDimensions));
        }
        catch (\Exception $ex) {
            $this->trace->info(TraceCode::COI_EXPERIMENT, ["metric error" => $ex]);
        }
    }

    public function getMetricDimensions(array $extra = []): array
    {
        return $extra + [
                'mode'              => $this->mode,
                'target'            => $this->gatewayFile->getTarget(),
                'type'              => $this->gatewayFile->getType()
            ];
    }
}
