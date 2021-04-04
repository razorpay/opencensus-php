<?php

namespace RZP\Models\GenericDocument;


use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\Entity;
use Illuminate\Support\Facades\Request;
use RZP\Models\Merchant\Detail\Service as MerchantDetailService;
use RZP\Models\Merchant\Document\Constants as DocumentConstants;

class Service extends Base\Service
{

    protected $response;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * This function is used when partner uploads files on behalf of account/ stakeholder
     * For both account / stakeholder, partner auth is used with X-Account-Id header
     *
     * @param array $input
     *
     * @return array
     */
    public function uploadDocument(array $input)
    {
        $merchant = $this->merchant;

        $lockId = 'UPLOAD_DOCUMENT_'.$merchant->getId();

        return $this->mutex->acquireAndRelease(

            $lockId,

            function() use ($merchant, $input) {

                $uploadResponse = $this->uploadMerchantDocument($merchant, $input);

                return ResponseHelper::getUploadFileResponse($uploadResponse);
            },
            Constants::DOCUMENT_UPLOAD_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_DOCUMENT_UPLOAD_OPERATION_IN_PROGRESS,
            Constants::DOCUMENT_UPLOAD_MUTEX_RETRY_COUNT);
    }

    /**
     * This function will upload files related to account or stakeholder by partner
     * Files of stakeholder will be fetched with account context. So using the same function for both
     * entities documents upload
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param array                       $input
     *
     * @return array
     */
    public function uploadMerchantDocument(\RZP\Models\Merchant\Entity $merchant, array $input)
    {

        $validator = (new Validator);

        $validator->validateInput('uploadDocument', $input);

        $validator->validateMimeType($input);

        $documentType = $input[Constants::PURPOSE];

        $param = [
            $documentType => $input[Entity::FILE]
        ];

        return $this->uploadFileToUFH($param);
    }

    public function uploadFileToUFH(array $input): array
    {
        $params = [];

        $ufhService = $this->app['ufh.service'];

        $merchantDetailsService = new MerchantDetailService();

        foreach ($input as $type => $file)
        {
            $fileName = $merchantDetailsService->getFileName($file, $this->merchant->getId());

            $fileMetaData = [
                DocumentConstants::CONTENT_DISPOSITION => DocumentConstants::CONTENT_DISPOSITION_INLINE
            ];

            $params[$type] = $ufhService->uploadFileAndGetResponse($file, $fileName, $type, $this->merchant, $fileMetaData);
        }

        return $params;
    }

    public function getDocument(array $input, string $documentId)
    {
        $fileStoreId = ResponseHelper::getDocumentId($documentId, Constants::DOCUMENT_ID_SIGN, Constants::FILE_ID_SIGN);

        $validator = (new Validator);

        $validator->validateInput('fetchDocument', $input);

        $signedUrlResponse = $this->getDocumentDownloadLinkFromUFH($input, $fileStoreId, $this->merchant->getId());

        $fileData = $this->fetchFiles([$fileStoreId], $this->merchant->getId());

        $this->validateFileResponse($fileData, [$fileStoreId]);

        $fileAttributes = $fileData['items'][0];

        $response = array_merge($fileAttributes, $signedUrlResponse);

        return ResponseHelper::getDownloadFileResponse($response);
    }

    public function getDocumentContent(array $input, string $fileStoreId)
    {
        $response = $this->getDocument($input, $fileStoreId);

        return redirect($response[Constants::URL], 302, Request::header(), true);
    }

    /**
     * The function returns the downloadable url in case the document belong to the merchant or its stakeholder from UFH
     *
     * @param array $input
     * @param string $fileStoreId
     * @param string|null $merchantId
     *
     * @return array|null
     * @throws Exception\BadRequestException
     */
    public function getDocumentDownloadLinkFromUFH(array $input, string $fileStoreId, string $merchantId = null): array
    {
        try
        {
            $ufhService = $this->app['ufh.service'];

            $input[Constants::DURATION] = $input[Constants::EXPIRY] ?? 15;

            unset($input[Constants::EXPIRY]);

            return $ufhService->getSignedUrl($fileStoreId, $input, $merchantId);
        }
        catch (\Exception $e)
        {
            $documentId = ResponseHelper::getDocumentId($fileStoreId, Constants::FILE_ID_SIGN, Constants::DOCUMENT_ID_SIGN);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_FILE_ACCESS, null, null, PublicErrorDescription::BAD_REQUEST_INVALID_FILE_ACCESS.':'.$documentId);
        }
    }

    public function fetchFiles(array $fileStoreIds, string $merchantId): array
    {
        $fileStoreIds = ResponseHelper::getDocumentIds($fileStoreIds, Constants::DOCUMENT_ID_SIGN, Constants::FILE_ID_SIGN);
        try
        {
            $ufhService = $this->app['ufh.service'];

            $input[Constants::IDS] = $fileStoreIds;

            $response = $ufhService->fetchFiles($input, $merchantId);

            $this->validateFileResponse($response, $fileStoreIds);

            return $response;
        }
        catch (Exception\BadRequestValidationFailureException $e)
        {
            throw $e;
        }
        catch (\Exception $e)
        {
            $documentIds = ResponseHelper::getDocumentIds($fileStoreIds, Constants::FILE_ID_SIGN, Constants::DOCUMENT_ID_SIGN);

            throw new Exception\ServerErrorException(
                'Error occurred while fetching files' . ':' . implode(', ', $documentIds),
                ErrorCode::BAD_REQUEST_SERVER_ERROR_FILE_FETCH_FAILURE);
        }
    }

    private function validateFileResponse(array $response, array $fileIds)
    {
        $fileData = $response['items'] ?? [];

        $validFileIds = array_column($fileData, Constants::ID);

        $invalidFileIds = array_diff($fileIds, $validFileIds);

        if (count($invalidFileIds) > 0)
        {
            $documentIds = ResponseHelper::getDocumentIds($invalidFileIds, Constants::FILE_ID_SIGN, Constants::DOCUMENT_ID_SIGN);

            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_FILE_IDS_PROVIDED . ': ' . implode(', ', $documentIds));
        }
    }
}
