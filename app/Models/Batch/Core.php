<?php

namespace RZP\Models\Batch;

use Mail;
use SplFileInfo;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Jobs\Reconciliation;
use RZP\Jobs\Batch as BatchJob;
use RZP\Models\FileStore\Utility;
use RZP\Exception\BadRequestException;
use RZP\Models\FileStore\Storage\AwsS3\Handler;

class Core extends Base\Core
{
    /**
     * Create flow: Creates new batch entity against given file id or against
     * given file(by first storing it).
     *
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param Base\PublicEntity|null $creator
     * @return Entity
     * @throws BadRequestException
     */
    public function create(array $input, Merchant\Entity $merchant, Base\PublicEntity $creator = null): Entity
    {
        //Need to be removed once the files are fixed
        if( ($input['type'] === 'iin_hitachi_visa') === true or
            ($input['type'] === 'iin_mc_mastercard') === true )
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_TYPE ,
                "type",$input['type'],
                "IIN Visa and MasterCard files are not to be uploaded"
            );
        }

        $this->trace->info(TraceCode::BATCH_CREATE_REQUEST, $input);

        $this->validateAdminRoleIfApplicable($input);

        $this->validatePermissionForBatchType($input);

        (new Validator)->validateLinkedAccountBatchActionAllowed($input, $merchant);

        $batch = (new Entity)->build($input);

        $batch->creator()->associate($creator);

        $batch->merchant()->associate($merchant);

        $batch->getValidator()->validateAuthForBatchType();

        $processor = Processor\Factory::get($batch);

        $this->emandateResponseFileInstrumentationIfApplicable($input, $processor);

        $ufhFile = $processor->storeInputFileAndSaveBatchWithSettings($input);

        if ((isset($input['type']) === true) and ($input['type'] === Type::RECONCILIATION))
        {
            $input['file_id'] = $ufhFile->getPublicId();
        }
        // Get the type. If type is migrated redirect to Batch MicroService.

        $dimensions = $batch->getMetricDimensions();

        if ($processor->shouldSendToBatchService())
        {
            $processor->addSettingsIfRequired($input);

            $batchResponse = $this->app->batchService->forwardToBatchServiceRequest($input, $merchant, $ufhFile);

            $this->trace->count(Metric::BATCH_REQUESTS_TOTAL, $dimensions);

            return (new ResponseEntity)->fill($batchResponse);
        }

        $this->trace->info(TraceCode::BATCH_CREATED, $batch->toArrayPublic());

        $this->trace->count(Metric::BATCH_REQUESTS_TOTAL, $dimensions);

        $this->dispatchOnQueueForProcessingIfApplicable($batch, $input);

        return $batch;
    }

    private function validateAdminRoleIfApplicable($input)
    {
        $auth = $this->app['basicauth'];

        if ($auth->isAdminAuth() === true)
        {
            (new Validator)->validateAdminRoleIfApplicable($auth->getAdmin(), $input[Entity::TYPE]);
        }
    }

    /**
     * If dedupe is set from FE or curl then pass its value in validate function,
     * To perform required permission validation on.
     * @param $input
     * @throws BadRequestException
     */
    private function validatePermissionForBatchType($input)
    {
        $auth = $this->app['basicauth'];

        if($auth->isAdminAuth() === true)
        {
            if($input[Entity::TYPE] === Type::SUB_MERCHANT)
            {
                $partnerOrgId = $this->repo->merchant->getMerchantOrg($input['config']['partner_id']);

                $org = $this->repo->org->findOrFail($partnerOrgId);

                (new Validator)->validatePermissionForDedupe($auth->getAdmin(), $input, $org);
            }
        }
    }

    /**
     * Validate flow: Stores and validates uploaded input file.
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @return array
     */
    public function storeAndValidateInputFile(array $input, Merchant\Entity $merchant): array
    {
        $this->trace->info(TraceCode::BATCH_FILE_VALIDATE_REQUEST, $input);

        (new Validator)->validateLinkedAccountBatchActionAllowed($input, $merchant);

        $batch = (new Entity)->build($input, 'validate');

        $batch->merchant()->associate($merchant);

        $processor = Processor\Factory::get($batch);

        $response = $processor->storeAndValidateInputFile($input);

        return $response;
    }

    /**
     * Internal Auth: There are some very rare cases (UFH issues) where output
     * file doesn't get created but the batch is actually processed. This has
     * happened specifically for payment_link type batch. We can't wrap the whole
     * operation under transaction because of few other reasons.
     *
     * TODO: Drop in detail the use case and reasons here.
     *
     * @param Entity $batch
     *
     * @return Entity
     */
    public function retryBatchOutputFile(Entity $batch): Entity
    {
        Processor\Factory::get($batch)->retryOutputFile();

        return $batch;
    }

    /**
     * Returns signed URL of the batch's most recent file
     * If batch is processed that will be the output file, else the batch input
     * file is returned.
     *
     * @param Entity $batch
     *
     * @return string
     */
    public function downloadBatch(Entity $batch): string
    {
        $file = $batch->latestFile();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        return $signedUrl;
    }

    /**
     * Process all pending batches. Called via CRON.
     *
     * This CRON runs every 6 hours and is for now only handling REFUND type.
     *
     * @return Base\PublicCollection
     */
    public function processBatches(): Base\PublicCollection
    {
        $this->increaseAllowedSystemLimits();

        $batches = $this->repo->batch->fetchUnprocessedForCron();

        foreach ($batches as $batch)
        {
            try
            {
                Processor\Factory::get($batch)->validateAndProcess();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e);
            }
        }

        return $batches;
    }

    public function fetchStatsOfBatch(Entity $batch): array
    {
        switch ($batch->getType())
        {
            case Type::PAYMENT_LINK:
                $stats = (new Invoice\Core)->fetchStatsOfBatch($batch);
                break;

            default:
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_STATS_NOT_SUPPORTED_FOR_TYPE,
                    Entity::TYPE,
                    [Entity::TYPE => $batch->getType()]);
        }

        $response = [
            Entity::ID    => $batch->getPublicId(),
            Entity::TYPE  => $batch->getType(),
            Entity::STATS => $stats,
        ];

        return $response;
    }

    public function fetchWithSettings(array $input, Merchant\Entity $merchant): array
    {
        $withConfig = array_pull($input, Entity::WITH_CONFIG, '0');

        $batches    = $this->repo->batch->fetch($input, $merchant->getId());

        $response   = $batches->toArrayPublic();

        // Conditionally, query and populate settings/config for each batch entity in response.
        if ($withConfig === '1')
        {
            // TODO: This is just temporary and not optimal query.
            $batches->each(function ($batch, $index) use (& $response)
            {
                $settingAccessor = Settings\Accessor::for($batch, Settings\Module::BATCH);

                $response[Base\PublicCollection::ITEMS][$index][Entity::CONFIG] = $settingAccessor->all()->toArray();
            });
        }

        return $response;
    }

    public function processBatchAsync(Entity $batch, array $input = []): Entity
    {
        $this->trace->info(TraceCode::BATCH_PROCESS_ASYNC, [$batch->toArrayPublic(), $input]);

        if (Type::isKubernetesJobQueueGroup($batch->getType()) === true)
        {
            if ($batch->getType() === Type::RECONCILIATION)
            {
                $k8sJobProcess = $this->ifProcessReconBatchViaK8sJob($batch, $input);

                if ($k8sJobProcess === true)
                {
                    return $batch;
                }
            }
        }

        BatchJob::dispatch($this->mode, $batch->getId(), $batch->getType(), $input);

        return $batch;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1000);
    }

    /**
     * Dispatches new job onto queue for asynchronous processing of it.
     * Only batch entity's of type in Type::QUEUE_GROUP gets pushed onto queue,
     * others are processed via CRON.
     *
     * @param Entity $batch
     * @param array  $input
     */
    protected function dispatchOnQueueForProcessingIfApplicable(Entity $batch, array $input)
    {
        if (Type::isKubernetesJobGroup($batch->getType()) === true)
        {
            unset($input[Entity::FILE]);
            $this->app->k8s_client->createJob($this->mode, $batch->getId(), $input, $batch->getType());

            return;
        }

        if (Type::isKubernetesJobQueueGroup($batch->getType()) === true)
        {
            if ($batch->getType() === Type::RECONCILIATION)
            {
                $k8sJobProcess = $this->ifProcessReconBatchViaK8sJob($batch, $input);

                if ($k8sJobProcess === true)
                {
                    return;
                }
            }
        }

        if  (Type::isQueueGroup($batch->getType()) === true)
        {
            unset($input[Entity::FILE]);

            BatchJob::dispatch($this->mode, $batch->getId(), $batch->getType(), $input);
        }
    }

    /**
     * Returns true if recon batch needs to be run via Kubernetes
     *
     * @param Entity $batch
     * @param array $input
     * @return bool
     */
    protected function ifProcessReconBatchViaK8sJob(Entity $batch, array $input = [])
    {
        // Get razorx treatment
        $variant = $this->app->razorx->getTreatment(
            $batch->getMerchantId(),
            Merchant\RazorxTreatment::K8S_RECON_BATCH_TREATMENT,
            $this->mode
        );

        if (strtolower($variant) === 'on')
        {
            unset($input[Entity::FILE]);
            Reconciliation::dispatch($this->mode, $batch->getId(), $input);

            return true;
        }
    }

    /**
     * @param string $outputFilePath
     * @param string $bucketType
     * @param bool   $downloadFile
     *
     *  Download the file from S3 bucket and return the downloaded local filePath
     * @return string
     * @throws \Exception
     */
    public function downloadAndGetFilePath(string $outputFilePath, string $bucketType, bool $downloadFile)
    {
        if ($downloadFile === false)
        {
            return null;
        }

        $outputFileNameArray = explode('/', $outputFilePath);
        $outputFileName      = end($outputFileNameArray);

        $dir = Utility::getStorageDir('files/filestore/batch/download/');

        $filePath = $dir . $outputFileName;

        $env = $this->app['env'];

        $handler = new Handler();

        $bucketConfig = $handler->getBucketConfig($bucketType, $env);

        $handler->saveAs($bucketConfig, $outputFilePath, $filePath);

        $this->trace->info(TraceCode::BATCH_SEND_MAIL_CONFIG,
                           ['bucketConfig'   => $bucketConfig,
                            'filePath'       => $filePath,
                            'outputFilePath' => $outputFilePath]);

        if(!is_readable($filePath))
        {
            throw new Exception\ServerNotFoundException("File Not Found",
                                                        ErrorCode::SERVER_ERROR_FILE_NOT_FOUND);
        }

        return $filePath;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \Exception
     */
    public function sendMail(array $input): array
    {
        $batch = $input[Entity::BATCH];

        $bucketType = $input[Entity::BUCKET_TYPE];

        $outputFilePath = $input[Entity::OUTPUT_FILE_PATH];

        $downloadFile = $input[Entity::DOWNLOAD_FILE];

        $settings = $input[Entity::SETTINGS];

        $type = $batch[Entity::TYPE];
        $type = studly_case($type);

        if ($settings == null)
        {
            $settings = [];
        }

        $merchantId = $batch['merchant_id'];
        $merchant   = $this->repo->merchant->findOrFailPublic($merchantId)->toArray();

        $filePath = $this->downloadAndGetFilePath($outputFilePath, $bucketType, $downloadFile);

        $mailerClass = "\\RZP\\Mail\\Batch\\$type";

        $mail = new $mailerClass(
            $batch,
            $merchant,
            $filePath,
            $settings);


        Mail::send($mail);

        return ['success' => true];
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \Exception
     */
    public function sendSMS(array $input): array
    {
        $batch = $input[Entity::BATCH];

        $type = $batch[Entity::TYPE];
        $type = studly_case($type);

        $merchantId = $batch['merchant_id'];
        $merchant   = $this->repo->merchant->findOrFailPublic($merchantId);

        $smsClass = "\\RZP\\SMS\\Batch\\$type";

        $sms = new $smsClass($batch, $merchant);

        try
        {
            if(empty($merchant->merchantDetail->getContactMobile()) === false)
            {
                $this->app->stork_service->sendSms($this->mode, $sms->getSMSPayload());
            }
        } catch (\Throwable $e) {
            $this->trace->traceException($e);
        }

        return ['success' => true];
    }

    protected function emandateResponseFileInstrumentationIfApplicable(& $input, $processor)
    {
        if((isset($input["type"]) === true) and
           (($input["type"] === "nach") or ($input["type"] === "emandate")) and
           (isset($input["sub_type"]) === true) and
           ($input["sub_type"] === "debit") and
           ($processor->shouldSendToBatchService() === true))
        {
            try
            {
                $input["config"]["response_file_name"] = (new SplFileInfo($input["file"]))->getFilename();
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::EMANDATE_INSTRUMENTATION_FILE_NAME_ADDITION_FAIL,
                    [
                        'input' => $input
                    ]);
            }
        }
    }
}
