<?php

namespace RZP\Models\Merchant\Document;

use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Partner\Constants as PartnerConstants;

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

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->mutex = $this->app['api.mutex'];

        $this->entityRepo = $this->repo->merchant_document;
    }

    /**
     * This function is used when partner uploads files on behalf of account/ stakeholder
     * For both account / stakeholder, partner auth is used with X-Account-Id header
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function uploadDocument(array $input)
    {
        $merchant = $this->merchant;

        return $this->mutex->acquireAndRelease(

            $merchant->getId(),

            function() use ($merchant, $input) {

                $accountV2Service = new Merchant\AccountV2\Service();

                $uploadResponse = $accountV2Service->uploadDocument($merchant, $input);

                return ResponseHelper::getUploadFileResponse($uploadResponse);
            },
            PartnerConstants::PARTNER_DOCUMENT_UPLOAD_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_DOCUMENT_UPLOAD_OPERATION_IN_PROGRESS,
            PartnerConstants::PARTNER_MUTEX_RETRY_COUNT);
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

    public function fetchActivationFilesFromDocument(string $mid = null)
    {
        $mid = $mid ?? $this->merchant->getId();

        return $this->core->fetchActivationFilesFromDocument($mid);
    }

    public function delete(string $id)
    {
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->delete($entity);

        return $response;
    }
}
