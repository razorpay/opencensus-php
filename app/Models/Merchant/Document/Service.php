<?php

namespace RZP\Models\Merchant\Document;

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
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use RZP\Models\Gateway\File\Constants as GatewayConstants;




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
     * @return array
     */
    public function uploadActivationFileMerchant(array $input)
    {
        $merchant = $this->merchant;

        return $this->mutex->acquireAndRelease(

            $merchant->getId(),

            function() use ($merchant, $input) {

                return $this->core->uploadActivationFile($this->merchant, $input);
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

        return $response;
    }

    public function getDocuments(string $accountId, string $entityType, string $entityId)
    {
        list($entity, $merchant) = $this->validateAndGetDocumentRequest($accountId, $entityType, $entityId);

        $timeStarted = millitime();

        $documentResponse =  (new DocumentResponse)->documentsResponse($merchant, $entityType, $entity->getId());

        $this->captureMetricsForDocumentFetch($entity, $timeStarted);

        return $documentResponse;
    }

    public function postDocumentsByPartner(string $accountId, string $entityType, string $entityId, array $input)
    {
        $timeStarted = millitime();

        list($entity, $merchant) = $this->validateAndGetDocumentRequest($accountId, $entityType, $entityId);

        $validator = (new Validator);

        $validator->validateInput('uploadDocument', $input);

        $validator->validateNeedsClarificationRespondedIfApplicable($merchant, $input);

        $validator->validateFileSize($input[Entity::FILE]);

        $documents = Type::getValidDocumentForEntity($entity->getEntity());

        if (in_array($input[Entity::DOCUMENT_TYPE], $documents) === false)
        {
            throw new Exception\BadRequestValidationFailureException('invalid document type:'. $input[Entity::DOCUMENT_TYPE] . ' for '. $entity->getEntity());
        }

        $this->uploadActivationFileByPartner($merchant, $entity, $input);

        $merchantDetails = $merchant->merchantDetail;

        $accountV2Core = (new AccountV2\Core());

        if($merchantDetails->getActivationStatus() === Detail\Status::NEEDS_CLARIFICATION)
        {
            $documentType = $input[Entity::DOCUMENT_TYPE];

            $ncAcknowledgementPayload = [$documentType => "uploaded"];

            $accountV2Core->updateNCFieldsAcknowledgedIfApplicable($ncAcknowledgementPayload, $merchant);
        }

        AutoUpdateMerchantProducts::dispatch(Product\Status::DOCUMENT_SOURCE ,$merchant, $merchantDetails);

        $documentResponse =  (new DocumentResponse)->documentsResponse($merchant, $entity->getEntity(), $entity->getId());

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
        (new Validator)->validateInput('firsDocumentRequest',$input);

        $merchantId = $this->merchant->getId();

        $from = strtotime($input['month'].'/01/'.$input['year']);
        $to = strtotime("+1 Month",$from);

        $documents = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,'firs_file',$from,$to);

        $documentMetaData=[];

        foreach ($documents as $document)
        {
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
    //for both individual files and as a zip.
    public function downloadFIRSDocuments(array $input)
    {
        (new Validator)->validateInput('firsDocumentRequest',$input);

        $merchantId = $this->merchant->getId();
        $document = null;

        if(isset($input['document_id'])===true)
        {
            $document = $this->repo->merchant_document->findDocumentById($input['document_id']);

            $signedURL = (new GenericDocument\Service)->getDocumentDownloadLinkFromUFH([], $document->getPublicFileStoreId(), $document->getMerchantId());
        }
        else {

            $from = strtotime($input['month'].'/01/'.$input['year']);
            $to = strtotime("+1 Month",$from);

            $documents = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,'firs_zip',$from,$to);

            if(isset($documents[0])){
                foreach ($documents as $file)
                {
                    $signedURL = (new GenericDocument\Service)->getDocumentDownloadLinkFromUFH([], $file->getPublicFileStoreId(), $file->getMerchantId());
                    $document = $file;
                    break;
                }
            }else{
                list($signedURL,$document) = $this->downloadZipFIRSFiles($input,$merchantId);
            }
        }

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

    //Function returns the signed_url to download the FIRS zip files for a month and year.
    protected function downloadZipFIRSFiles(array $input, string $merchantId)
    {
        $from = strtotime($input['month'].'/01/'.$input['year']);
        $to = strtotime("+1 Month",$from);

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
}
