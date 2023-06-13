<?php

namespace RZP\Models\Merchant\Document;

use RZP\Constants\HyperTrace;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\GenericDocument;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Services\UfhService;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\AccountV2;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\Detail\NeedsClarification;
use \WpOrg\Requests\Exception as RequestsException;
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use RZP\Models\Gateway\File\Constants as GatewayConstants;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Trace\Tracer;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Jobs\MerchantFirsDocumentsZip;
use function Doctrine\Common\Cache\Psr6\get;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    protected $mutex;

    protected $ufh;


    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->mutex = $this->app['api.mutex'];

        $this->entityRepo = $this->repo->merchant_document;

        $this->ufh = (new UfhService($this->app));

    }

    /**
     * upload a document in MerchantDocument table
     *
     * @param array $input
     *
     * @param string $merchantId
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function uploadActivationFileMerchant(array $input)
    {
        $merchant = $this->merchant;

        return $this->uploadActivationFileForMerchant($input, $merchant);
    }

    public function uploadActivationFileForMerchant(array $input, $merchant)
    {
        return $this->mutex->acquireAndRelease(

            $merchant->getId(),

            function() use ($merchant, $input) {
                return $this->core->uploadActivationFile($merchant, $input);
            },

            Merchant\Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Merchant\Constants::MERCHANT_MUTEX_RETRY_COUNT);
    }

    public function uploadFilesByAgent(array $input)
    {
        (new Validator())->validateInput(__FUNCTION__, $input);

        $merchantId = $input['merchant_id'];

        (new Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $merchantCore = new Merchant\Core();

        $merchant = $merchantCore->get($merchantId);

        $documentType = $input[Entity::DOCUMENT_TYPE];

        $param = [
            $documentType => $input[Entity::FILE]
        ];

        $adminId = $this->auth->getAdmin()->getId();

        $document = (new Entity)->generateId();

        $document->setUploadByAdminId($adminId);

        $document->merchant()->associate($merchant);

        $fileAttributes = (new Detail\Service())->storeActivationFile($document, $param);

        $params = [$documentType => $fileAttributes[$documentType]];

        $uploadedDocument = $this->core->storeInMerchantDocument($merchant, $merchant, $params, $document);

        $documentMetaData = [
            Entity::ID                 => $uploadedDocument[$documentType]->getId(),
            Entity::FILE_STORE_ID      => $uploadedDocument[$documentType]->getFileStoreId(),
            Entity::MERCHANT_ID        => $uploadedDocument[$documentType]->getMerchantId(),
            Entity::UPLOAD_BY_ADMIN_ID => $uploadedDocument[$documentType]->getUploadByAdminId(),
            Entity::CREATED_AT         => $uploadedDocument[$documentType]->getCreatedAt()
        ];

        return $documentMetaData;
    }

    protected function uploadActivationFileByPartner(Merchant\Entity $account, Base\PublicEntity $entity, array $input)
    {
        return $this->mutex->acquireAndRelease(

            $account->getId(),

            function() use ($account, $input, $entity) {

                return $this->core->uploadActivationFile($account, $input, 'true', 'uploadDocument', $entity);
            },

            Merchant\Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Merchant\Constants::MERCHANT_MUTEX_RETRY_COUNT);
    }

    public function fetchActivationFilesFromDocument(string $mid = null)
    {
        $mid = $mid ?? $this->merchant->getId();

        return $this->core->fetchActivationFilesFromDocument($mid);
    }

    public function merchantDocumentDelete(string $merchantId, string $id)
    {
        $merchant = (new Merchant\Core())->get($merchantId);

        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $merchant);

        $this->repo->deleteOrFail($entity);

        return [
            'success' => true
        ];
    }

    public function delete(string $id)
    {
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->delete($entity);

        $merchantId =  $this->merchant->getMerchantId();

        // route request to PGOS for deletion
        try
        {

            $payload = [
                "merchant_id" => $merchantId,
                "id"          => $id,
            ];

            $this->trace->info(TraceCode::PGOS_DOCUMENT_DELETE_REQUEST, [
                '$payload' => $payload,
            ]);

            $pgosProxyController = new MerchantOnboardingProxyController();

            $pgosResponse = $pgosProxyController->handlePGOSProxyRequests('merchant_document_delete', $payload, $this->merchant);

            $this->trace->info(TraceCode::PGOS_DOCUMENT_DELETE_RESPONSE, [
                'merchant_id' => $merchantId,
                'response'    => $pgosResponse,
            ]);
        }
        catch (RequestsException $e) {

            if (checkRequestTimeout($e) === true) {
                $this->trace->info(TraceCode::PGOS_PROXY_TIMEOUT, [
                    'merchant_id' => $merchantId,
                ]);
            } else {
                $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                    'merchant_id' => $merchantId,
                    'error_message' => $e->getMessage()
                ]);
            }

        }
        catch (\Throwable $exception) {
            // this should not introduce error counts as it is running in shadow mode
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id' => $merchantId,
                'error_message' => $exception->getMessage()
            ]);
        }

        return $response;
    }

    public function getDocuments(string $accountId, string $entityType, string $entityId)
    {
        list($entity, $merchant) = $this->validateAndGetDocumentRequest($accountId, $entityType, $entityId);

        $timeStarted = millitime();

        $documentResponse = Tracer::inspan(['name' => HyperTrace::DOCUMENT_V2_GET_RESPONSE], function () use ($merchant, $entityType, $entity) {

            return (new DocumentResponse)->documentsResponse($merchant, $entityType, $entity->getId());
        });

        $this->captureMetricsForDocumentFetch($entity, $timeStarted);

        return $documentResponse;
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\BadRequestException
     */
    public function postDocumentsByPartner(string $accountId, string $entityType, string $entityId, array $input)
    {
        $timeStarted = millitime();

        list($entity, $merchant) = $this->validateAndGetDocumentRequest($accountId, $entityType, $entityId);

        $validator = (new Validator);

        $validator->validateDocumentTypeAndFileType('uploadDocument', $input);
        
        $validator->validateNeedsClarificationRespondedIfApplicable($merchant, $input);

        $validator->validateFileSize($input[Entity::FILE]);

        $documents = Type::getValidDocumentForEntity($entity->getEntity());

        if (in_array($input[Entity::DOCUMENT_TYPE], $documents) === false)
        {
            throw new Exception\BadRequestValidationFailureException('invalid document type:'. $input[Entity::DOCUMENT_TYPE] . ' for '. $entity->getEntity());
        }

        Tracer::inspan(['name' => HyperTrace::UPLOAD_ACTIVATION_FILE], function () use ($merchant, $entity, $input) {

            $this->uploadActivationFileByPartner($merchant, $entity, $input);
        });

        $merchantDetails = $merchant->merchantDetail;

        $accountV2Core = (new AccountV2\Core());

        if($merchantDetails->getActivationStatus() === Detail\Status::NEEDS_CLARIFICATION)
        {
            $documentType = $input[Entity::DOCUMENT_TYPE];

            $ncAcknowledgementPayload = [$documentType => "uploaded"];

            Tracer::inspan(['name' => HyperTrace::UPDATE_NC_FIELDS_ACKNOWLEDGED], function () use ($accountV2Core, $ncAcknowledgementPayload, $merchant) {

                $accountV2Core->updateNCFieldsAcknowledgedIfApplicable($ncAcknowledgementPayload, $merchant);
            });

            Tracer::inspan(['name' => HyperTrace::UPDATE_NC_FIELDS_ACKNOWLEDGED_FOR_NO_DOC], function () use ($accountV2Core, $merchant, $merchantDetails) {

                $noDocGmvLimitExhausted = $accountV2Core->isNoDocOnboardingGmvLimitExhausted($merchant);

                if ($noDocGmvLimitExhausted === true) {
                    (new NeedsClarification\Core())->updateNCFieldsAcknowledgedIfApplicableForNoDoc($merchant, $merchantDetails);
                }
            });
        }

        AutoUpdateMerchantProducts::dispatch(Product\Status::DOCUMENT_SOURCE ,$merchant->getId());

        $documentResponse = Tracer::inspan(['name' => HyperTrace::DOCUMENT_V2_GET_RESPONSE], function () use ($merchant, $entity) {

            return (new DocumentResponse)->documentsResponse($merchant, $entity->getEntity(), $entity->getId());
        });

        $this->captureMetricsForDocumentUpload($entity, $input, $timeStarted);

        return $documentResponse;

    }

    private function captureMetricsForDocumentUpload($entity, array $input, $timeStarted)
    {
        $dimensions = [
            'entity'        => $entity->getEntity(),
            'document_type' => $input[Entity::DOCUMENT_TYPE]
        ];

        $this->trace->count(Metric::DOCUMENT_UPLOAD_V2_SUCCESS_TOTAL, $dimensions);
        $this->trace->histogram(Metric::DOCUMENT_UPLOAD_V2_SUCCESS_TOTAL, millitime() - $timeStarted, $dimensions);
    }

    private function captureMetricsForDocumentFetch($entity, $timeStarted)
    {
        $dimensions = [
            'entity' => $entity->getEntity(),
        ];

        $this->trace->count(Metric::DOCUMENT_FETCH_V2_SUCCESS_TOTAL, $dimensions);
        $this->trace->histogram(Metric::DOCUMENT_FETCH_V2_TIME_IN_MS, millitime() - $timeStarted, $dimensions);
    }

    protected function validateAndGetDocumentRequest(string $accountId, string $entityType, string $entityId)
    {
        Account\Entity::verifyIdAndStripSign($accountId);
        $account   = $this->repo->merchant->findOrFailPublic($accountId);

        (new Account\Core)->validatePartnerAccess($this->merchant, $account->getId());

        $partner = $this->merchant;

        // Document V2 API is exposed to partner private auth. But we need the submerchant context during document upload
        // since few of the internal file upload flow uses $this->merchant as merchant. (\RZP\Services\UfhService::createUfhClient)
        // So setting submerchant context here to avoid this.
        $this->app['basicauth']->setMerchant($account);


        if (E::MERCHANT === $entityType)
        {
            return [$account, $account];
        }
        else
        {
            Stakeholder\Entity::verifyIdAndStripSign($entityId);

            $stakeHolder = $this->repo->stakeholder->findOrFailPublic($entityId);

            $stakeHolder->getValidator()->validateAccountStakeholder($account, $stakeHolder);

            return [$stakeHolder, $account];
        }
    }

    public function getDocumentTypes()
    {
        $types = Type::VALID_DOCUMENTS;

        return (new Base\PublicCollection($types))->toArrayWithItems();
    }

    //This function fetches all the FIRS documents for that merchant in a particular
    //month and year.
    public function fetchFIRSDocuments(array $input)
    {
        (new Validator)->validateInput('firsDocumentFetchRequest',$input);

        $merchantId = $this->merchant->getId();

        $from = strtotime($input['month'].'/01/'.$input['year']);
        $to = strtotime("+1 Month",$from)-1;

        // Query for RBL + ICICI Firstdata + ICICI Zip Files
        $documents = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypesAndDate($merchantId, ['firs_file', 'firs_firstdata_file', 'firs_icici_zip'], $from, $to);

        $documentMetaData=[];

        foreach ($documents as $document)
        {
            // For Zip Files Check Status Before Sending Documents to FE
            if($document['document_type'] === 'firs_icici_zip' and $this->isZippedFIRSDocumentProcessed($document) === false)
            {
                continue;
            }

            $documentResponse = [
                Entity::ID              => $document->getId(),
                Entity::DOCUMENT_TYPE   => $document->getDocumentType(),
                Entity::MERCHANT_ID     => $document->getMerchantId(),
                Entity::FILE_STORE_ID   => $document->getFileStoreId(),
                Entity::CREATED_AT      => $document->getCreatedAt(),
            ];
            array_push($documentMetaData,$documentResponse);
        }

        return $documentMetaData;
    }

    //This function returns signed_url to download/view the FIRS documents in a particular month and year
    //for individual files.
    public function downloadFIRSDocuments(array $input)
    {
        (new Validator)->validateInput('firsDocumentDownloadRequest',$input);

        $document = $this->repo->merchant_document->findDocumentById($input['document_id']);
        $signedURL = (new GenericDocument\Service)->getDocumentDownloadLinkFromUFH([], $document->getPublicFileStoreId(), $document->getMerchantId());

        $documentMetaData = [
            Entity::ID              => $document->getId(),
            Entity::DOCUMENT_TYPE   => $document->getDocumentType(),
            Entity::MERCHANT_ID     => $document->getMerchantId(),
            Entity::FILE_STORE_ID   => $document->getFileStoreId(),
            Entity::CREATED_AT      => $document->getCreatedAt(),
            Entity::SIGNED_URL      => $signedURL['signed_url'],
        ];

        $this->trace->info(TraceCode::FILES_DOWNLOAD,array_except($documentMetaData,[Entity::SIGNED_URL]));

        return $documentMetaData;
    }

    /*  Function returns the signed_url to download the FIRS zip files for a month and year.
        Disabling Use of this function because removing download all option from frontend
        which was to used to zip individual documents in realtime, removing now because of
        zipped files are now shown for icici firs documents.
    */

    protected function downloadZipFIRSFiles(array $input, string $merchantId)
    {
        $from = strtotime($input['month'].'/01/'.$input['year']);
        $to = strtotime("+1 Month",$from) - 1;

        $ufhService = $this->app['ufh.service'];

        $documents = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,'firs_file',$from,$to);

        $fileIds=[];

        foreach ($documents as $file)
        {
            array_push($fileIds,$file->getPublicFileStoreId());
        }

        $prefix = "Firs";

        $zipFileId = $ufhService->downloadFiles($fileIds,$merchantId,$prefix);

        $this->trace->info(TraceCode::BULK_DOWNLOAD,[
            'success' => isset($zipFileId),
        ]);

        $documentDate = strtotime($input['month'].'/'.date('d').'/'.$input['year']);

        $document = $this->core->saveInMerchantDocument([
            GatewayConstants::ID => $zipFileId],
            $merchantId,'firs_zip',$documentDate);

        $signedURL = (new GenericDocument\Service)->getDocumentDownloadLinkFromUFH([], $document->getPublicFileStoreId(), $document->getMerchantId());

        return [$signedURL,$document];

    }

    /*
     * Cron Executes and Start Collecting all the Merchant Ids that have received
     * ICICI FIRS Files in the last month and dispatch those merchant Ids with month
     * and year for collecting file Ids and zipping them for future download.
    */
    public function collectAndZipFIRSDocuments(array $input)
    {

        (new Validator)->validateInput('firsZippingCronRequest',$input);

        /*
         * Job is responsible for creating zip files by aggregating individual pdf files and
         * expects merchant_id, month, year and force_create.
         *
         * force_create flag is responsible for deleting older zip files and its entries and
         * creating new ones. This is just a failsafe to ensure, if any zip_file was stuck or
         * wasn't able to process gracefully.
        */

        $this->trace->info(TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_CRON_REQUEST,[
            'input'   => $input
        ]);

        $minimumDelay = 15;

        if(isset($input['force_create']) && $input['force_create'] === true)
        {
            $month = $input['month'];
            $year = $input['year'];

            $iterationNumber = 0;

            foreach ($input['merchant_ids'] as $merchantId)
            {
                $payload = [
                    'merchant_id'   => $merchantId,
                    'month'         => $month,
                    'year'          => $year,
                    'force_create'  => true,
                    'mode'          => $this->mode,
                ];

                // Assign a delay between 0 & 900 so that tasks are distributed over 15 minute period
                MerchantFirsDocumentsZip::dispatch($payload)->delay($iterationNumber*$minimumDelay % 901);

                $iterationNumber++;

                $this->trace->info(TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_JOB_DISPATCH,$payload);
            }
        }
        else
        {
            $currentTimeStamp = Carbon::now(Timezone::IST)->getTimeStamp();
            $currentMonth = explode('/',date('m/d/Y', $currentTimeStamp))[0];
            $currentYear = explode('/',date('m/d/Y', $currentTimeStamp))[2];

            $previousMonth = Carbon::now(Timezone::IST)->subMonth();

            // year and month for which we are generating firs zipped file containing all icici firs documents
            $year  = $previousMonth->year;
            $month = $previousMonth->month;

            $from = strtotime($month.'/01/'.$year);
            $to = $currentTimeStamp;
            $documentType = 'firs_icici_file';

            $merchantEntries = $this->repo->merchant_document->findAllMerchantsAndDistinctDatedDocumentsAddedInRangeWithDocumentType($documentType,$from,$to);

            $iterationNumber = 0;

            foreach ($merchantEntries as $entry)
            {
                $merchantId = $entry[Entity::MERCHANT_ID];
                $documentDate = $entry[Entity::DOCUMENT_DATE];

                list($month,$date,$year) = explode('/',date('m/d/Y', $documentDate));

                if($month === $currentMonth && $year === $currentYear){
                    continue;
                }

                // check if Zip file already exists and a new zip file creation is required.
                // Skip if no new document for a month after zip creation

                $from = strtotime($month.'/01/'.$year);
                $to = strtotime("+1 Month",$from)-1;

                $latestZippedFIRSDocument =  $this->repo->merchant_document->findLatestDocumentForMerchantIdAndDocumentTypeInRange($merchantId,"firs_icici_zip",$from,$to);

                if(isset($latestZippedFIRSDocument) === true)
                {
                    $latestIndividualFIRSDocument = $this->repo->merchant_document->findLatestDocumentForMerchantIdAndDocumentTypeInRange($merchantId,"firs_icici_file",$from,$to);

                    if($latestZippedFIRSDocument->getCreatedAt() >= $latestIndividualFIRSDocument->getCreatedAt())
                    {

                        $this->trace->info(TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_SKIPPED,
                        [
                            "message" => "Latest Zipped File Already Contains All Individual Files",
                            "latestZippedFIRSDocument" => $latestZippedFIRSDocument->getId(),
                            "latestIndividualFIRSDocument" => $latestIndividualFIRSDocument->getId(),
                            "latestZippedFileCreatedAt" => $latestZippedFIRSDocument->getCreatedAt(),
                            "latestIndividualFileCreatedAt" => $latestIndividualFIRSDocument->getCreatedAt()
                        ]);

                        continue;
                    }
                }

                $payload = [
                    'merchant_id'   => $merchantId,
                    'month'         => $month,
                    'year'          => $year,
                    'force_create'  => false,
                    'mode'          => $this->mode,
                ];

                // Assign a delay between 0 & 900 so that tasks are distributed over 15 minute period
                MerchantFirsDocumentsZip::dispatch($payload)->delay($iterationNumber*$minimumDelay % 901);

                $iterationNumber++;

                $this->trace->info(TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_JOB_DISPATCH,$payload);
            }
        }

        return ['success' => true];
    }

    protected function isZippedFIRSDocumentProcessed($document) : bool
    {
        $ufhFileStoreEntity = (new GenericDocument\Service)->getDocumentDownloadLinkFromUFH([], $document->getPublicFileStoreId(), $document->getMerchantId());

        if((isset($ufhFileStoreEntity['status']) === true) && ($ufhFileStoreEntity['status'] === 'uploaded'))
        {
            return true;
        }
        else
        {
            $this->trace->info(TraceCode::FETCH_ZIPPED_FIRS_DOCUMENT_STATUS_FAILED,[
                'merchant_id'   => $document->getMerchantId(),
                'file_store_id'  => $document->getPublicFileStoreId(),
                'status'        => $ufhFileStoreEntity['status'],
            ]);
            return false;
        }
    }

    public function getSignedUrl(string $documentId)
    {
        return $this->core->getSignedUrl($documentId);
    }

}
