<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\BusinessDetail\Entity as MerchantBusinessDetailEntity;
use RZP\Models\Merchant\BusinessDetail\Constants as MerchantBusinessDetailConstants;

class BusinessDetail
{
    protected MerchantV1\BusinessDetail $proto;

    function __construct(MerchantV1\BusinessDetail $proto)
    {
        $this->proto = $proto;
    }

    public function ToEntity(): MerchantBusinessDetailEntity
    {
        $merchantBusinessDetailRawAttributes = [
            MerchantBusinessDetailEntity::ID => $this->proto->getId(),
            MerchantBusinessDetailEntity::MERCHANT_ID => $this->proto->getMerchantId(),
            MerchantBusinessDetailEntity::WEBSITE_DETAILS => $this->proto->getWebsiteDetailsUnwrapped(),
            MerchantBusinessDetailEntity::PLUGIN_DETAILS => $this->proto->getPluginDetailsUnwrapped(),
            MerchantBusinessDetailEntity::APP_URLS  => $this->proto->getAppUrlsUnwrapped(),
            MerchantBusinessDetailEntity::BLACKLISTED_PRODUCTS_CATEGORY => $this->proto->getBlacklistedProductsCategoryUnwrapped(),
            MerchantBusinessDetailEntity::BUSINESS_PARENT_CATEGORY => $this->proto->getBusinessParentCategoryUnwrapped(),
            MerchantBusinessDetailEntity::CREATED_AT => $this->proto->getCreatedAt(),
            MerchantBusinessDetailEntity::UPDATED_AT => $this->proto->getUpdatedAt(),
            MerchantBusinessDetailEntity::LEAD_SCORE_COMPONENTS => $this->proto->getLeadScoreComponentsUnwrapped(),
            MerchantBusinessDetailEntity::ONBOARDING_SOURCE => $this->proto->getOnboardingSourceUnwrapped(),
            MerchantBusinessDetailEntity::PG_USE_CASE => $this->proto->getPgUseCaseUnwrapped(),
            MerchantBusinessDetailEntity::MIQ_SHARING_DATE => $this->proto->getMiqSharingDateUnwrapped(),
            MerchantBusinessDetailEntity::TESTING_CREDENTIALS_DATE => $this->proto->getTestingCredentialsDateUnwrapped(),
            MerchantBusinessDetailEntity::METADATA => $this->proto->getMetadataUnwrapped(),
            MerchantBusinessDetailEntity::AUDIT_ID => $this->proto->getAuditIdUnwrapped(),
            MerchantBusinessDetailConstants::GST_DETAILS => $this->proto->getGstDetailsUnwrapped(),
        ];

        $merchantBusinessDetailEntity = new MerchantBusinessDetailEntity();
        $merchantBusinessDetailEntity->setRawAttributes($merchantBusinessDetailRawAttributes);
        $merchantBusinessDetailEntity->exists = true;
        return $merchantBusinessDetailEntity;
    }
}
