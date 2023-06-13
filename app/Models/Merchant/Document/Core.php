<?php

namespace RZP\Models\Merchant\Document;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\Partner;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Stakeholder;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use \WpOrg\Requests\Exception as RequestsException;
use RZP\Models\FileStore\Entity as FileStoreEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Gateway\File\Constants as GatewayConstants;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class Core extends Base\Core
{
    private $merchantCore;

    public function __construct()
    {
        parent::__construct();

        $this->merchantCore = new Merchant\Core();
    }

    public function setMerchantCore($merchantCore)
    {
        $this->merchantCore = $merchantCore;
    }

    /**
     * @param Entity $document
     *
     * @return mixed
     */
    public function delete(Entity $document)
    {
        $merchantDetailCore = new Detail\Core();

        $this->trace->info(TraceCode::DOCUMENT_DELETE_REQUEST, ['id' => $document->getId()]);

        $this->repo->deleteOrFail($document);

        return $merchantDetailCore->createResponse($this->merchant->merchantDetail);

    }

    /**
     * this function creates or edit a new document with params documentType and fileStoreId
     *
     * @param Merchant\Entity   $merchant
     * @param Base\PublicEntity $entity
     * @param array             $params
     * @param Entity|null       $inputDocument
     *
     * @return array
     * @throws BadRequestException
     */
    public function storeInMerchantDocument(Merchant\Entity $merchant, Base\PublicEntity $entity, array $params, Entity $inputDocument = null): array
    {
        $this->trace->info(TraceCode::DOCUMENT_CREATE_REQUEST, ['input' => $params]);

        //
        // Ideally inputDocument should be not null only if params contain only one document data
        //
        if (count($params) > 1 and $inputDocument !== null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_NOT_SUPPORTED_FEATURE);
        }

        $uploadedDocuments = [];

        foreach ($params as $documentType => $fileAttributes)
        {
            $metadata = [
                'file_name' => $fileAttributes[Constants::ORIGINAL_FILE_NAME] ?? ''
            ];

            $input = [
                Entity::FILE_STORE_ID => $fileAttributes[Constants::FILE_ID],
                Entity::SOURCE        => $fileAttributes[Constants::SOURCE],
                Entity::DOCUMENT_TYPE => $documentType,
                Entity::METADATA      => $metadata
            ];

            FileStoreEntity::verifyIdAndSilentlyStripSign($input[Entity::FILE_STORE_ID]);

            $document = $inputDocument ?? (new Entity)->generateId();

            $document->edit($input);

            $document->entity()->associate($entity);

            $document->merchant()->associate($merchant);

            $this->repo->saveOrFail($document);

            $uploadedDocuments[$documentType] = $document;
        }

        return $uploadedDocuments;
    }

    /**
     * this function store activation file in merchantDocument by new route.
     *
     * @param Merchant\Entity        $merchant
     * @param array                  $input
     * @param bool                   $validateLock
     *
     * @param string                 $rule
     * @param Base\PublicEntity|null $entity
     *
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    public function uploadActivationFile(
        Merchant\Entity $merchant, array $input, bool $validateLock = true, $rule = 'uploadDocument', Base\PublicEntity $entity = null)
    {
        (new Validator())->validateDocumentTypeAndFileType($rule, $input);

        $this->trace->info(TraceCode::DOCUMENT_CREATE_REQUEST, ['input' => $input]);

        $merchantDetailCore = new Detail\Core();

        $merchantDetails = $merchantDetailCore->getMerchantDetails($merchant);

        if ($validateLock === true)
        {
            $merchantDetails->getValidator()->validateIsNotLocked();
        }

        $documentType = $input[Entity::DOCUMENT_TYPE];

        $param = [
            $documentType => $input[Entity::FILE]
        ];

        $document = (new Entity)->generateId();

        $document->merchant()->associate($merchant);
        $fileAttributes = (new Detail\Service())->storeActivationFile($document, $param);
        $merchantId = $merchant->getMerchantId();

        // route request to PGOS
        try {

            $payload = [
                "document_type" => $documentType,
                "file_store_id" => $fileAttributes[$documentType]['file_id'],
                "merchant_id" => $merchantId,
                "original_file_name" => $fileAttributes[$documentType]['original_file_name'],
            ];

            $this->trace->info(TraceCode::PGOS_DOCUMENT_CREATE_REQUEST, [
                '$payload' => $payload,
            ]);

            $pgosProxyController = new MerchantOnboardingProxyController();

            $response = $pgosProxyController->handlePGOSProxyRequests('merchant_document_upload', $payload, $merchant);

            $this->trace->info(TraceCode::PGOS_DOCUMENT_CREATE_RESPONSE, [
                'merchant_id' => $merchantId,
                'response' => $response,
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

        $entity = $entity ?? $merchant;

        $this->saveMerchantDocument($merchant, $documentType, $fileAttributes[$documentType], $entity, $validateLock, $document);

        if (empty($input['is_partner_kyc']) === false)
        {
            $partnerDetailCore = new Partner\Core();

            return $partnerDetailCore->createPartnerResponse($merchantDetails);
        }

        return $merchantDetailCore->createResponse($merchantDetails);

    }

    // Upload the document to s3 bucket save the details in merchant documents table
    public function internalUploadFile(
        Merchant\Entity $merchant, array $input, $rule = 'uploadDocument', Base\PublicEntity $entity = null)
    {
        (new Validator)->validateInput($rule, $input);

        $this->trace->info(TraceCode::DOCUMENT_CREATE_REQUEST, ['input' => $input]);

        $documentType = $input[Entity::DOCUMENT_TYPE];

        $param = [
            $documentType => $input[Entity::FILE]
        ];

        $document = (new Entity)->generateId();

        $document->merchant()->associate($merchant);

        $fileAttributes = (new Detail\Service())->storeActivationFile($document, $param);

        $entity = $entity ?? $merchant;

        $uploadedDocuments = $this->storeInMerchantDocument($merchant, $entity, $fileAttributes, $document);

        return $uploadedDocuments[$documentType];
    }

    /**
     * @param Merchant\Entity   $merchant
     * @param string            $documentType
     * @param array             $fileAttributes
     * @param Base\PublicEntity $entity
     * @param bool              $validateLock
     * @param null              $document
     *
     */
    public function saveMerchantDocument(Merchant\Entity $merchant, string $documentType, array $fileAttributes,
                                         Base\PublicEntity $entity, bool $validateLock = true, $document = null)
    {
        $params = [$documentType => $fileAttributes];

        $merchantDetailCore = new Detail\Core();

        $merchantDetails = $merchantDetailCore->getMerchantDetails($merchant);

        if ($validateLock === true)
        {
            $merchantDetails->getValidator()->validateIsNotLocked();
        }

        $this->repo->transaction(function() use ($documentType, $merchant, $merchantDetails, $params, $document, $entity) {

            $uploadedDocuments = $this->storeInMerchantDocument($merchant, $entity, $params, $document);

            $document = $uploadedDocuments[$documentType];

            $this->PerformOcrIfApplicable(
                $merchantDetails,
                $document,
                $merchant
            );

            (new Detail\Core())->updateDocumentVerificationStatus($merchant, $merchantDetails, $document->getDocumentType());

            $this->repo->saveOrFail($merchantDetails);

            $this->repo->saveOrFail($document);
        });

        $this->pushEventsAndMetrics($merchant, $params);
    }

    /**
     * @param string $merchantId
     *
     * @return array
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    public function fetchActivationFilesFromDocument(string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $documentsResponse = $this->documentResponse($merchant);

        $detailService = new Detail\Service();

        foreach ($documentsResponse as $documentType => &$documentMetaData)
        {
            foreach ($documentMetaData as &$document)
            {
                $signedUrl = $detailService->getSignedUrl($document[Entity::FILE_STORE_ID], $document[Entity::MERCHANT_ID]);

                $document[Entity::SIGNED_URL] = $signedUrl;
            }
        }

        return $documentsResponse;
    }

    /**
     * this function takes array of fileStoreIds and delete them.
     *
     * @param array $fileStoreIds
     */
    public function deleteDocuments(array $fileStoreIds)
    {
        foreach ($fileStoreIds as $fileStoreId)
        {
            $document = $this->repo->merchant_document->findDocumentByFileStoreId($fileStoreId);

            if (isset($document) === true)
            {
                $this->delete($document);
            }
        }
    }

    /**
     * this function takes array of document ids and delete them.
     *
     * @param array $ids
     */
    public function deleteDocumentsbyId(array $ids)
    {
        foreach ($ids as $id)
        {
            $document = $this->repo->merchant_document->findDocumentById($id);

            if (isset($document) === true)
            {
                $this->delete($document);
            }
        }
    }

    /**
     * this function takes fileStoreId and delete the document.
     *
     * @param string $fileStoreId
     */
    public function deleteDocument(string $fileStoreId)
    {
        $document = $this->repo->merchant_document->findDocumentByFileStoreId($fileStoreId);

        if (isset($document) === true)
        {
            $this->trace->info(TraceCode::DOCUMENT_DELETE_REQUEST, ['id' => $document->getId()]);

            $this->repo->deleteOrFail($document);
        }
    }


    /**
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function documentResponse(Merchant\Entity $merchant): array
    {
        $documentsResponse = [];

        $merchants = (new Merchant\Core)->getAllMerchantsMappedToMerchantLegalEntity($merchant);

        if ($merchants->isEmpty() === false)
        {
            $documents = $this->repo->merchant_document->findDocumentsForMerchantIds($merchants->getIds());
        }
        else
        {
            $documents = $merchant->merchantDocuments;
        }

        foreach ($documents as $document)
        {
            $documentMetaData = [
                Entity::ID => $document->getId(),
                Entity::FILE_STORE_ID => $document->getFileStoreId(),
                Entity::MERCHANT_ID => $document->getMerchantId(),
                Entity::CREATED_AT => $document->getCreatedAt(),
                Entity::METADATA => $document->getMetadata()
            ];

            if (isset($documentsResponse[$document->getDocumentType()]) === false)
            {
                $documentsResponse[$document->getDocumentType()] = [];
            }

            array_push($documentsResponse[$document->getDocumentType()], $documentMetaData);
        }

        return $documentsResponse;
    }

    /** Returns FileStoreId for given document type
     *
     * @param Merchant\Entity $merchant
     * @param string          $documentType
     *
     * @return null|string
     */
    public function getPublicFileStoreIdForDocumentType(Merchant\Entity $merchant, string $documentType): ?string
    {

        $documents = $merchant->merchantDocuments;

        foreach ($documents as $document)
        {
            if ($document->getDocumentType() === $documentType)
            {
                return $document->getPublicFileStoreId();
            }
        }

        return null;
    }

    public function shouldPerfomOcrOnDocumentUpload(
        Entity $document, Merchant\Entity $merchant, Merchant\Detail\Entity $merchantDetails): bool
    {

        if (Type::isDocumentTypeToPerformOcr($document->getDocumentType()) === false)
        {
            return false;
        }

        if ((Type::isPoaDocument($document->getDocumentType()) === true) and
            ($this->merchantCore->isAutoKycEnabled($merchantDetails, $merchant) === false))
        {
            return false;
        }

        return true;
    }

    protected function PerformOcrIfApplicable(
        Merchant\Detail\Entity $merchantDetails,
        Entity $document,
        Merchant\Entity $merchant)
    {

        if ($this->shouldPerfomOcrOnDocumentUpload($document, $merchant, $merchantDetails) === false)
        {
            return;
        }

        $this->performOcrWithBvs($document, $merchant, $merchantDetails);

        $this->trace->count(Detail\Metric::MERCHANT_DOCUMENT_OCR_PERFORMED_TOTAL,
                            [
                                Entity::DOCUMENT_TYPE        => $document->getDocumentType(),
                                Detail\Entity::BUSINESS_TYPE => $merchantDetails->getBusinessTypeValue()
                            ]);
    }

    public function getSignedUrl(string $documentId)
    {
        $this->trace->info(TraceCode::FETCH_SIGNED_URL, [
            'document' => $documentId
        ]);

        $document = $this->repo->merchant_document->findDocumentById($documentId);

        if($document === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, $documentId, 'Document id is not valid.');
        }

        try {
            $signedUrl = (new Detail\Service())->getSignedUrl($document[Entity::FILE_STORE_ID], $document[Entity::MERCHANT_ID], $document[Entity::SOURCE]);
        }
        catch (\Exception $e )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_UFH_INTEGRATION, null , $documentId, 'Signed url could not be fetched for this document id.');
        }

        return [
            'id'            => $documentId,
            'file_store_id' => $document[Entity::FILE_STORE_ID],
            'metadata'      => $document[Entity::METADATA],
            'merchant_id'   => $document[Entity::MERCHANT_ID],
            'created_at'    => $document[Entity::CREATED_AT],
            'signed_url'    => $signedUrl
        ];

    }

    protected function mapToKycDocType(string $document_type): ?string
    {
        if ($document_type === Type::AADHAR_BACK or $document_type === Type::AADHAR_FRONT)
        {
            return Type::AADHAAR;
        }
        if ($document_type === Type::PASSPORT_BACK or $document_type === Type::PASSPORT_FRONT)
        {
            return Type::PASSPORT;
        }
        if ($document_type === Type::VOTER_ID_BACK or $document_type === Type::VOTER_ID_FRONT)
        {
            return Type::VOTERS_ID;
        }

        return null;
    }

    protected function pushEventsAndMetrics(Merchant\Entity $merchant, array $input)
    {
        foreach ($input as $documentType => $file)
        {
            $eventAttributes = [Constants::DOCUMENT_TYPE => $documentType];

            $this->trace->count(Detail\Metric::MERCHANT_DOCUMENT_TYPE_SUBMITTED_TOTAL,
                                [
                                    Entity::DOCUMENT_TYPE => $documentType
                                ]);

            $this->app['diag']->trackOnboardingEvent(EventCode::KYC_UPLOAD_DOCUMENT_SUCCESS, $merchant, null, $eventAttributes);
        }
    }

    public function performOcrWithBvs(Entity $document, Merchant\Entity $merchant, Detail\Entity $merchantDetails)
    {
        // If document type belong to the category of joint validation document,
        // we need to send both documents together in one request.
        // Currently, this is hidden behind experiment.
        $isExperimentEnabledForJointValidation = (new Merchant\Core)->isRazorxExperimentEnable($merchant->getMerchantId(),
            RazorxTreatment::AADHAAR_FRONT_AND_BACK_JOINT_VALIDATION);

        if ($isExperimentEnabledForJointValidation and Type::isJointValidationDocumentType($document->getDocumentType()) === true)
        {
            $this->trace->info(TraceCode::BVS_JOINT_VALIDATION_REQUEST, [
                'performOcrWithBvs' => 'aadhaar front and back joint validation experiment.',
                '$merchant' => $merchant->getMerchantId()
                ]);
            $factory = new requestDispatcher\Factory();

            $requestDispatcher = $factory->getBvsRequestDispatcherForDocument(
                $document, $merchant, $merchantDetails);

            if(empty($requestDispatcher)===false)
            {
                $this->trace->debug(TraceCode::BVS_JOINT_VALIDATION_REQUEST, [
                    'triggerBVSRequest' => 'request dispatcher non-empty.'
                ]);
                $requestDispatcher->triggerBVSRequest();
            }
        }
        else if (Type::isPoaDocument($document->getDocumentType()) === true)
        {
            $this->performPoaOcrWithBvs($document, $merchantDetails,$merchant);
        }
        else
        {
            $factory = new requestDispatcher\Factory();

            $requestDispatcher = $factory->getBvsRequestDispatcherForDocument(
                $document, $merchant, $merchantDetails);

            if(empty($requestDispatcher)===false)
            {
                $requestDispatcher->triggerBVSRequest();
            }
        }
    }

    /**
     * @param Entity          $document
     * @param Detail\Entity   $merchantDetails
     * @param Merchant\Entity $merchant
     */
    public function performPoaOcrWithBvs(Entity $document, Detail\Entity $merchantDetails, Merchant\Entity $merchant)
    {

        $artefactDetails = Constant::FIELD_ARTEFACT_DETAILS_MAP[$document->getDocumentType()] ?? [];

        $artefactType       = $artefactDetails[Constant::ARTEFACT_TYPE] ?? '';
        $artefactProofIndex = $artefactDetails[Constant::PROOF_INDEX] ?? '1';

        $payload = [
            Constant::ARTEFACT_TYPE   => $artefactType,
            Constant::CONFIG_NAME     => $artefactType,
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::NAME => $merchantDetails->getPromoterPanName(),
            ],
            Constant::PROOFS          => [
                $artefactProofIndex => [Constant::UFH_FILE_ID => $document->getPublicFileStoreId()],
            ],
        ];

        $bvsValidation = (new AutoKyc\Bvs\Core($merchant,$merchantDetails,$document))->verify(
            $merchantDetails->getId(),
            $payload);


        if (empty($bvsValidation) === false and $bvsValidation->getValidationStatus() == BvsValidationConstants::CAPTURED)
        {

            $document->setValidationId($bvsValidation->getValidationId());

            $merchantDetails->setPoaVerificationStatus(null);

            $exists = (new Stakeholder\Core)->checkIfStakeholderExists($merchantDetails);
            if ($exists === true)
            {
                $merchantDetails->stakeholder->setPoaStatus(null);
            }

            $this->repo->merchant_detail->saveOrFail($merchantDetails);
        }

    }

    /**
     * for a merchant, Return all Bvs_validations for all ocr documents
     *
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function fetchAllOcrDocumentsBvsValidations(Merchant\Entity $merchant): array
    {
        $documents = $merchant->merchantDocuments;

        $bvsValidations = [];

        foreach ($documents as $document)
        {
            if (Type::isPoaDocument($document->getDocumentType()) === false)
            {
                continue;
            }

            if (empty($document->getValidationId()) === false)
            {
                $bvsValidations[] = $document->bvsValidation;
            }
        }

        return $bvsValidations;
    }

    public function getDocument(string $merchantId,string $validationId)
    {
        return $this->repo->merchant_document->findDocumentsForMerchantIdAndValidationId($merchantId, $validationId);
    }

    public function saveInMerchantDocument(array $response, string $merchantId, string $documentType, int $documentDate = null)
    {
        $input = [
            Entity::FILE_STORE_ID => $response[GatewayConstants::ID],
            Entity::SOURCE        => 'UFH',
            Entity::DOCUMENT_TYPE => $documentType,
            Entity::DOCUMENT_DATE => $documentDate,
        ];

        FileStoreEntity::verifyIdAndSilentlyStripSign($input[Entity::FILE_STORE_ID]);

        $document = $inputDocument ?? (new Entity)->generateId();

        $document->edit($input);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $document->entity()->associate($merchant);

        $document->merchant()->associate($merchant);

        $this->repo->saveOrFail($document);

        return $document;

    }

    public function saveFileInMerchantDocument($fileId, $documentType, $mid, $entity, $entityId)
    {
        $input = [
            Entity::FILE_STORE_ID => $fileId,
            Entity::SOURCE        => 'UFH',
            Entity::DOCUMENT_TYPE => $documentType,
        ];

        FileStoreEntity::verifyIdAndSilentlyStripSign($input[Entity::FILE_STORE_ID]);
        $document = $inputDocument ?? (new Entity)->generateId();
        $document->edit($input);
        $document->setMerchantId($mid);
        $document->setEntityType($entity);
        $document->setAttribute(Entity::ENTITY_ID, $entityId);

        $this->repo->saveOrFail($document);

        return $document;
    }

    public function savePGOSDataToAPI(array $data)
    {
        $splitzResult = (new Detail\Core)->getSplitzResponse($data[Entity::MERCHANT_ID], 'pgos_migration_dual_writing_exp_id');

        if ($splitzResult === 'variables')
        {
            $merchant = $this->repo->merchant->find($data[Entity::MERCHANT_ID]);

            // dual write only for below merchants
            // merchants for whom pgos is serving onboarding requests
            // merchants who are not completely activated
            if ($merchant->getService() === Merchant\Constants::PGOS and
                $merchant->merchantDetail->getActivationStatus()!=Detail\Status::ACTIVATED)
            {
                $document = $this->repo->merchant_document->findDocumentByFileStoreId($data[Entity::FILE_STORE_ID]);

                if (empty($document) === false)
                {
                    unset($data[Entity::MERCHANT_ID]);

                    $data[Entity::SOURCE] = 'UFH';

                    $document->edit($data);

                    $this->repo->merchant_document->saveOrFail($document);
                }
                else
                {
                    $document = new Entity;

                    $data[Entity::SOURCE] = 'UFH';

                    $data[Entity::ENTITY_TYPE] = 'merchant';

                    $data[Entity::ENTITY_ID] = $data[Entity::MERCHANT_ID];

                    $document->generateId();

                    $document->build($data);

                    $this->repo->merchant_document->saveOrFail($document);

                }
            }
        }
    }
}
