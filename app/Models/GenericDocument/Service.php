<?php

namespace RZP\Models\GenericDocument;


use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\Core;
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

    public function getDocument(array $input, string $fileStoreId)
    {
        $signedUrlResponse = $this->getDocumentDownloadLinkFromUFH($input, $fileStoreId);

        return  ResponseHelper::getDownloadFileResponse($signedUrlResponse);
    }

    public function getDocumentContent(array $input, string $fileStoreId)
    {
        $response = $this->getDocument($input, $fileStoreId);

        return Redirect($response[Constants::URL], 302, Request::header(), true);
    }

    /**
     * The function returns the downloadable url in case the document belong to the merchant or its stakeholder from UFH
     * @param array  $input
     * @param string $fileStoreId
     *
     * @return array|null
     */
    public function getDocumentDownloadLinkFromUFH(array $input, string $fileStoreId): array
    {
        try
        {
            $ufhService = $this->app['ufh.service'];

            $input[Constants::DURATION] = $input[Constants::EXPIRY] ?? 15;

            unset($input[Constants::EXPIRY]);

            return $ufhService->getSignedUrl($fileStoreId, $input, $this->merchant->getId());
        }
        catch (\Exception $e)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_FILE_DOWNLOAD);
        }
    }
}