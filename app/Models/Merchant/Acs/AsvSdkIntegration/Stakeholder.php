<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;


use Razorpay\Asv\RequestMetadata;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Stakeholder\Entity as MerchantStakeholderEntity;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\Stakeholder as MerchantStakeholderProtoMapper;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\Address as AddressProtoMapper;

use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;

class Stakeholder extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getByMerchantId(string $merchantId, RequestMetadata $requestMetadata = null): PublicCollection
    {
        /**
         * @var MerchantV1\StakeholderResponseByMerchantId $response
         */
        list($response, $err) = $this->asvSdkClient->getStakeholder()->getByMerchantId(
            $merchantId,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null){
            $this->handleError($err);
        }

        $stakeholdersArray = [];

        $stakeholders = $response->getStakeholders();

        /**
         * @var $stakeholder MerchantV1\Stakeholder
         */
        foreach ($stakeholders as $stakeholder) {
            $merchantStakeholderProtoConvertor = new MerchantStakeholderProtoMapper($stakeholder);
            $stakeholderEntity = $merchantStakeholderProtoConvertor->ToEntity();
            $stakeholdersArray[] = $stakeholderEntity;
        }

        return (new MerchantStakeholderEntity)->newCollection($stakeholdersArray);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getById(string $id, RequestMetadata $requestMetadata = null): MerchantStakeholderEntity
    {
        /**
         * @var MerchantV1\StakeholderResponse $response
         */
        list($response, $err) = $this->asvSdkClient->getStakeholder()->getById(
            $id,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $stakeholder = $response->getStakeholder();

        return (new MerchantStakeholderProtoMapper($stakeholder))->ToEntity();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getAddressForStakeholder(string $stakeholderId, RequestMetadata $requestMetadata = null): ?\RZP\Models\Address\Entity
    {
        /**
         * @var MerchantV1\AddressResponseByStakeholderId $response
         */
        list($response, $err) = $this->asvSdkClient->getAddress()->getByStakeholderId(
            $stakeholderId,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $address = $response->getAddress();

        /* If address is null, meaning address is not set for the stakeholder */
        if($address === null) {
            return null;
        }

        return (new AddressProtoMapper($address))->ToEntity();
    }

    /**
     * @throws \Exception
     */
    public function getAddressForStakeholderIgnoreInvalidArgument(string $stakeholderId, ?RequestMetadata $requestMetadata = null): ?\RZP\Models\Address\Entity
    {
        try {
            $address = $this->getAddressForStakeholder($stakeholderId, $requestMetadata);
        } catch (\Exception $e) {
            if($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                return null;
            }

            throw $e;
        }

        return $address;
    }

    public function getAddressForStakeholderIgnoreInvalidArgumentCallBack(string $stakeholderId, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function() use ($stakeholderId, $requestMetadata) {
            return $this->getAddressForStakeholderIgnoreInvalidArgument($stakeholderId, $requestMetadata);
        };
    }
}
