<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;

class MerchantWebsite
{

    protected MerchantV1\MerchantWebsite $proto;

    function __construct(MerchantV1\MerchantWebsite $proto)
    {
        $this->proto = $proto;
    }

    public function ToEntity(): MerchantWebsiteEntity
    {
        $merchantWebsiteRawAttributes = [
            MerchantWebsiteEntity::ID => $this->proto->getId(),
            MerchantWebsiteEntity::MERCHANT_ID => $this->proto->getMerchantId(),
            MerchantWebsiteEntity::DELIVERABLE_TYPE => $this->proto->getDeliverableTypeUnwrapped(),
            MerchantWebsiteEntity::SHIPPING_PERIOD => $this->proto->getShippingPeriodUnwrapped(),
            MerchantWebsiteEntity::REFUND_REQUEST_PERIOD => $this->proto->getRefundRequestPeriodUnwrapped(),
            MerchantWebsiteEntity::REFUND_PROCESS_PERIOD => $this->proto->getRefundProcessPeriodUnwrapped(),
            MerchantWebsiteEntity::WARRANTY_PERIOD => $this->proto->getWarrantyPeriodUnwrapped(),

            MerchantWebsiteEntity::MERCHANT_WEBSITE_DETAILS => $this->proto->getMerchantWebsiteDetailsUnwrapped(),
            MerchantWebsiteEntity::ADMIN_WEBSITE_DETAILS => $this->proto->getAdminWebsiteDetailsUnwrapped(),
            MerchantWebsiteEntity::ADDITIONAL_DATA => $this->proto->getAdditionalDataUnwrapped(),
            MerchantWebsiteEntity::STATUS => $this->proto->getStatusUnwrapped(),
            MerchantWebsiteEntity::GRACE_PERIOD => $this->proto->getGracePeriodUnwrapped(),
            MerchantWebsiteEntity::SEND_COMMUNICATION => $this->proto->getSendCommunicationUnwrapped(),
            MerchantWebsiteEntity::AUDIT_ID => $this->proto->getAuditId(),

            MerchantWebsiteEntity::CREATED_AT => $this->proto->getCreatedAt(),
            MerchantWebsiteEntity::UPDATED_AT => $this->proto->getUpdatedAt(),
        ];
        $merchantWebsiteEntity = new MerchantWebsiteEntity();
        $merchantWebsiteEntity->setRawAttributes($merchantWebsiteRawAttributes);
        $merchantWebsiteEntity->exists = true;
        return $merchantWebsiteEntity;
    }
}
