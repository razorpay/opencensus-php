<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use RZP\Models\Merchant\BusinessDetail\Constants as MerchantBusinessDetailConstants;

class BusinessDetail implements EntityToProtoConvertorInterface
{
    protected BusinessDetailEntity $entity;

    function __construct(BusinessDetailEntity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @throws \Exception
     */
    public function toSaveProtoRequest(): MerchantV1\SaveRequest
    {
        $saveRequest = new MerchantV1\SaveRequest();

        $businessDetail = new MerchantV1\BusinessDetail();

        $rawAttributes = $this->entity->getAttributes();

        $businessDetail->setId(Helper::notNullCheck($rawAttributes, BusinessDetailEntity::ID));
        $businessDetail->setMerchantId(Helper::notNullCheck($rawAttributes, BusinessDetailEntity::MERCHANT_ID));
        $businessDetail->setAuditId(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::AUDIT_ID));
        $businessDetail->setWebsiteDetails(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::WEBSITE_DETAILS));
        $businessDetail->setAppUrls(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::APP_URLS));
        $businessDetail->setGstDetails(Helper::converToStringValue($rawAttributes, MerchantBusinessDetailConstants::GST_DETAILS));
        $businessDetail->setBusinessParentCategory(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::BUSINESS_PARENT_CATEGORY));
        $businessDetail->setBlacklistedProductsCategory(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::BLACKLISTED_PRODUCTS_CATEGORY));
        $businessDetail->setPluginDetails(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::PLUGIN_DETAILS));
        $businessDetail->setOnboardingSource(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::ONBOARDING_SOURCE));
        $businessDetail->setLeadScoreComponents(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::LEAD_SCORE_COMPONENTS));
        $businessDetail->setPgUseCase(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::PG_USE_CASE));
        $businessDetail->setMetadata(Helper::converToStringValue($rawAttributes, BusinessDetailEntity::METADATA));
        $businessDetail->setMiqSharingDate(Helper::convertToInt32Value($rawAttributes, BusinessDetailEntity::MIQ_SHARING_DATE));
        $businessDetail->setTestingCredentialsDate(Helper::convertToInt32Value($rawAttributes, BusinessDetailEntity::TESTING_CREDENTIALS_DATE));

        $saveRequest->setMerchantBusinessDetail($businessDetail);
        return $saveRequest;
    }
}
