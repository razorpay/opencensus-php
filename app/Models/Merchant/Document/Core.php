<?php

namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;

class Core extends Base\Core
{
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
     * @param Merchant\Entity $merchant
     * @param array           $params
     * @param Entity|null     $inputDocument
     *
     * @return array
     * @throws BadRequestException
     */
    public function storeInMerchantDocument(Merchant\Entity $merchant, array $params, Entity $inputDocument = null) : array
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
            $input = [
                Entity::FILE_STORE_ID => $fileAttributes[Constants::FILE_ID],
                Entity::SOURCE        => $fileAttributes[Constants::SOURCE],
                Entity::DOCUMENT_TYPE => $documentType,
            ];

            $document = $inputDocument ?? (new Entity)->generateId();

            $document->edit($input);

            $document->merchant()->associate($merchant);

            $document->setEntityType();

            $this->repo->saveOrFail($document);

            $uploadedDocuments[$documentType] = $document;
        }

        return $uploadedDocuments;
    }

    /**
     * this function store activation file in merchantDocument by new route.
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param bool            $validateLock
     *
     * @return array
     * @throws BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\LogicException
     */
    public function uploadActivationFile(Merchant\Entity $merchant, array $input, bool $validateLock = true)
    {
        (new Validator)->validateInput('uploadDocument', $input);

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

        $this->repo->transaction(function() use ($documentType, $merchant, $merchantDetails, $fileAttributes, $document) {

            $uploadedDocuments = $this->storeInMerchantDocument($merchant, $fileAttributes, $document);

            $document = $uploadedDocuments[$documentType];

            $this->PerformOcrIfApplicable(
                $merchantDetails,
                $document,
                $merchant);

            $this->repo->saveOrFail($document);
        });

        $this->pushEventsAndMetrics($merchant, $input);

        return $merchantDetailCore->createResponse($merchantDetails);
    }

    /**
     * @param string $merchantId
     *
     * @return array
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\LogicException
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
                Entity::ID            => $document->getId(),
                Entity::FILE_STORE_ID => $document->getFileStoreId(),
                Entity::MERCHANT_ID   => $document->getMerchantId(),
            ];

            if (isset($documentsResponse[$document->getDocumentType()]) === false)
            {
                $documentsResponse[$document->getDocumentType()] = [];
            }

            array_push($documentsResponse[$document->getDocumentType()], $documentMetaData);
        }

        return $documentsResponse;
    }

    protected function PerformOcrIfApplicable(
        Merchant\Detail\Entity $merchantDetails,
        Entity $document,
        Merchant\Entity $merchant)
    {
        $merchantCore = new Merchant\Core();

        if ((Type::isDocumentTypeToPerformOcr($document->getDocumentType()) === false) or
            ($merchantCore->isAutoKycEnabled($merchantDetails, $merchant) === false))
        {
            return;
        }

        if ((new Detail\Core())->isOcrEnabledThroughBvs($merchantDetails) === true)
        {
            (new Detail\Core())->performOcrWithBvs($document, $merchantDetails);
        }
        else
        {
            $this->verifyPOA($document, $merchantDetails);
        }

        $this->trace->count(Detail\Metric::MERCHANT_DOCUMENT_OCR_PERFORMED_TOTAL,
                            [
                                Entity::DOCUMENT_TYPE        => $document->getDocumentType(),
                                Detail\Entity::BUSINESS_TYPE => $merchantDetails->getBusinessTypeValue()
                            ]);
    }

    /**
     * @param Entity        $document
     * @param Detail\Entity $merchantDetails
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\LogicException
     */
    protected function verifyPOA(Entity $document, Merchant\Detail\Entity $merchantDetails)
    {
        $signedUrl = (new Detail\Service())->getSignedUrl(
            $document->getFileStoreId(),
            $document->getMerchantId()
        );

        $input = [
            DetailConstant::SIGNED_URL        => $signedUrl,
            DetailConstant::DOCUMENT_TYPE     => $this->mapToKycDocType($document->getDocumentType()),
            DetailConstant::DOCUMENT_FILE_ID  => $document->getFileStoreId(),
            DetailConstant::PROMOTER_PAN_NAME => $merchantDetails->getPromoterPanName(),
            DetailConstant::DOCUMENT_SOURCE   => $document->getFileStoreSource(),
        ];

        $verificationStatus = OcrVerificationStatus::FAILED;

        try
        {
            $verificationStatus = (new AutoKyc\Core())->verifyPOA($merchantDetails, $input);
        }
        catch (\Throwable $exception)
        {
            $data = [
                Entity::FILE_STORE_ID => $document->getFileStoreId(),
            ];

            $this->trace->traceException($exception,
                                         null,
                                         TraceCode::MERCHANT_POA_VERIFICATION_FAILED,
                                         $data);
        }

        $document->setOcrVerify($verificationStatus);
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
        $eventAttributes = [];

        if (empty($input[Entity::DOCUMENT_TYPE]) === false)
        {
            $eventAttributes[Constants::DOCUMENT_TYPE] = $input[Entity::DOCUMENT_TYPE];
        }

        $this->trace->count(Detail\Metric::MERCHANT_DOCUMENT_TYPE_SUBMITTED_TOTAL,
                            [
                                Entity::DOCUMENT_TYPE => $input[Entity::DOCUMENT_TYPE]
                            ]);

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_UPLOAD_DOCUMENT_SUCCESS, $merchant, null, $eventAttributes);
    }
}
