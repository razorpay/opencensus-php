<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Website\Entity;
use RZP\Models\Merchant\Website\Entity as MerchantWebsite;
use \Google\Protobuf\StringValue;

class Website implements EntityToProtoConvertorInterface
{
    protected MerchantWebsite $entity;

    protected array $dirtyFieldKeys;

    function __construct(MerchantWebsite $entity,array $dirtyFieldKeys)
    {
        $this->entity = $entity;

        $this->dirtyFieldKeys =  $dirtyFieldKeys;
    }

    /**
     * @throws \Exception
     */
    public function toSaveProtoRequest(): MerchantV1\SaveRequest
    {
        $saveRequest = new MerchantV1\SaveRequest();

        $website = new MerchantV1\MerchantWebsite();

        $merchantWebsiteSaveRequest = new MerchantV1\MerchantWebsiteSaveRequest();

        $rawAttributes = $this->entity->getAttributes();

        $website->setId(Helper::notNullCheck($rawAttributes, Entity::ID));
        $website->setMerchantId(Helper::notNullCheck($rawAttributes, Entity::MERCHANT_ID));
        $website->setDeliverableType(Helper::converToStringValue($rawAttributes, Entity::DELIVERABLE_TYPE));
        $website->setShippingPeriod(Helper::converToStringValue($rawAttributes,Entity::SHIPPING_PERIOD));
        $website->setRefundRequestPeriod(Helper::converToStringValue($rawAttributes, Entity::REFUND_REQUEST_PERIOD));
        $website->setRefundProcessPeriod(Helper::converToStringValue($rawAttributes, Entity::REFUND_PROCESS_PERIOD));
        $website->setWarrantyPeriod(Helper::converToStringValue($rawAttributes,Entity::WARRANTY_PERIOD));
        $website->setMerchantWebsiteDetails(Helper::converToStringValue($rawAttributes, Entity::MERCHANT_WEBSITE_DETAILS));
        $website->setAdminWebsiteDetails(Helper::converToStringValue($rawAttributes, Entity::ADMIN_WEBSITE_DETAILS));
        $website->setAdditionalData(Helper::converToStringValue($rawAttributes, Entity::ADDITIONAL_DATA));
        $website->setStatus(Helper::converToStringValue($rawAttributes, Entity::STATUS));
        $website->setGracePeriod(Helper::convertToInt32ValueFromBool($rawAttributes, Entity::GRACE_PERIOD));
        $website->setSendCommunication(Helper::convertToInt32ValueFromBool($rawAttributes, Entity::SEND_COMMUNICATION));
        $website->setAuditId($rawAttributes[Entity::AUDIT_ID] ?? "");

        $merchantWebsiteSaveRequest->setMerchantWebsite($website);
        $merchantWebsiteSaveRequest->setFields($this->dirtyFieldKeys);

        $saveRequest->setMerchantWebsiteSaveRequest($merchantWebsiteSaveRequest);
        return $saveRequest;
    }
}
