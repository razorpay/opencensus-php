<?php

namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\FileStore\Core as FileStoreCore;
use RZP\Models\Merchant\Detail\Verifiers\FactoryVerifier;
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
        $this->trace->info(TraceCode::DOCUMENT_DELETE_REQUEST, ['id' => $document->getId()]);

        return $this->repo->deleteOrFail($document);
    }

    /**
     * this function creates or edit a new document with params documentType and fileStoreId
     * @param Merchant\Entity $merchant
     * @param array           $params
     * @param Entity|null     $document
     */
    public function storeInMerchantDocument(Merchant\Entity $merchant, array $params, Entity $document = null)
    {
        $this->trace->info(TraceCode::DOCUMENT_CREATE_REQUEST, ['input' => $params]);

        foreach ($params as $documentType => $fileStoreId)
        {
            $input = [
                Entity::FILE_STORE_ID => $fileStoreId,
                Entity::DOCUMENT_TYPE => $documentType,
            ];

            $document = $document ?? (new Entity)->generateId();

            $document->edit($input);

            $document->merchant()->associate($merchant);

            $document->setEntityType();

            $this->repo->saveOrFail($document);
        }
    }

    /**
     * this function store activation file in merchantDocument by new route.
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param bool            $validateLock
     *
     * @return array
     */
    public function uploadActivationFile(Merchant\Entity $merchant, array $input, bool $validateLock = true)
    {
        $merchantDetailCore = new Detail\Core();

        $this->trace->info(TraceCode::DOCUMENT_CREATE_REQUEST, ['input' => $input]);

        $merchantDetails = $merchantDetailCore->getMerchantDetails($merchant);

        if ($validateLock === true)
        {
            $merchantDetails->getValidator()->validateIsNotLocked();
        }

        $document = (new Entity)->generateId()->build($input);

        $document->merchant()->associate($merchant);

        $document->setEntityType();

        $this->repo->transaction(function() use ($document, $merchant, $input, $merchantDetails)
        {
            $this->repo->saveOrFail($document);

            $param = [
                $input[Entity::DOCUMENT_TYPE] => $input[Entity::FILE]
            ];

            $param = (new Detail\Service())->storeActivationFile($document, $param);

            $this->storeInMerchantDocument($merchant, $param, $document);

            $this->handleAndPerformOcrForUnRegisteredBusinessType(
                $merchantDetails,
                $document,
                $merchant);

            $this->repo->saveOrFail($document);
        });


        return $merchantDetailCore->createResponse($merchantDetails);
    }

    /**
     * @param string $merchantId
     *
     * @return array
     */
    public function fetchActivationFilesFromDocument(string $merchantId): array
    {
        $documentsResponse = $this->documentResponse($merchantId);

        $detailService = new Detail\Service();

        foreach ($documentsResponse as $documentType => &$documentMetaData)
        {
            foreach($documentMetaData as &$document)
            {
                $signedUrl = $detailService->getSignedUrl($document[Entity::FILE_STORE_ID], $merchantId);

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

    /** R
     * @param string $merchantId
     *
     * @return array
     */
    public function documentResponse(string $merchantId): array
    {
        $documents = $this->repo->merchant_document->findAllDocumentsByMerchantID($merchantId);

        $documentsResponse = [];

        foreach ($documents as $document)
        {
            $documentMetaData = [
                Entity::ID            => $document->getId(),
                Entity::FILE_STORE_ID => $document->getFileStoreId()
            ];

            if (isset($documentsResponse[$document->getDocumentType()]) === false)
            {
                $documentsResponse[$document->getDocumentType()] = [];
            }

            array_push($documentsResponse[$document->getDocumentType()], $documentMetaData);
        }

        return $documentsResponse;
    }

    protected function handleAndPerformOcrForUnRegisteredBusinessType(
        Merchant\Detail\Entity $merchantDetails,
        Entity $document,
        Merchant\Entity $merchant)
    {
        $merchantCore = new Merchant\Core();

        if ((Type::isDocumentTypeToPerformOcr($document->getDocumentType()) === false) or
            ($merchantCore->isUnRegisteredOnBoardingEnabled($merchant, $merchantDetails->isUnregisteredBusiness()) === false))
        {
            return;
        }

        $ocrResponse = $this->performOcr($document);

        $ocrDetails = $this->extractDetailFromOcrResponse($ocrResponse);

        $promoterPanName = $merchantDetails->getPromoterPanName();

        $ocrMatchingPercentage = 0;

        if ((isset($ocrDetails[Constants::NAME]) === true) and
            empty($promoterPanName) === false)
        {
            $ocrMatchingPercentage = get_similar_text_percent($promoterPanName, $ocrDetails[Constants::NAME]);
        }

        $this->setOcrVerificationStatus($document, $ocrMatchingPercentage);
    }


    protected function extractDetailFromOcrResponse($ocrResponse): array
    {
        if ((empty($ocrResponse) === true) or
            (($ocrResponse instanceof Detail\Verifiers\PoaVerifierResponse) === false))
        {
            return [];
        }

        $ocrDetails = [
            Constants::NAME => $ocrResponse->getOcrName()
        ];

        return $ocrDetails;
    }

    protected function performOcr(Entity $document)
    {
        $signedUrl = (new FileStoreCore)->getSignedUrl(
            $document->getFileStoreId(),
            $document->getMerchantId()
        );

        $input = [
            DetailConstant::SIGNED_URL => $signedUrl
        ];

        try
        {
            $verifier = FactoryVerifier::getPoaVerifier($input);

            return $verifier->verifyDetails();
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

            return null;
        }
    }

    protected function setOcrVerificationStatus(Entity $document, $percent)
    {
        $ocrVerifiedStatus = OcrVerificationStatus::FAILED;

        if ($percent >= OcrVerificationStatus::OCR_VERIFICATION_THRESHOLD)
        {
            $ocrVerifiedStatus = OcrVerificationStatus::VERIFIED;
        }
        $document->setOcrVerify($ocrVerifiedStatus);
    }
}
