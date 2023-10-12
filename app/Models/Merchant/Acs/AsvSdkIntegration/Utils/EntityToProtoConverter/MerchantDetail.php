<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Google\Rpc\Help;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Detail\Constants as MerchantDetailConstant;
use RZP\Models\Merchant\Detail\Entity;

class MerchantDetail implements EntityToProtoConvertorInterface
{
    protected Entity $entity;
    /**
     * @param \RZP\Models\Merchant\Detail\Entity $entity
     */

    protected array $dirtyFieldKeys;

    public function __construct(\RZP\Models\Merchant\Detail\Entity $entity, array $dirtyFieldKeys)
    {
        $this->entity = $entity;

        $this->dirtyFieldKeys = $dirtyFieldKeys;
    }

    /**
     * @throws \Exception
     */
    public function toSaveProtoRequest(): MerchantV1\SaveRequest
    {
        $saveRequest = new MerchantV1\SaveRequest();

        $detailEntity = new MerchantV1\MerchantDetail();

        $rawAttributes = $this->entity->getAttributes();

        $detailEntity->setMerchantId(Helper::notNullCheck($rawAttributes, Entity::MERCHANT_ID));
        $detailEntity->setContactName(Helper::converToStringValue($rawAttributes, Entity::CONTACT_NAME));
        $detailEntity->setContactEmail(Helper::converToStringValue($rawAttributes, Entity::CONTACT_EMAIL));
        $detailEntity->setContactMobile(Helper::converToStringValue($rawAttributes, Entity::CONTACT_MOBILE));
        $detailEntity->setContactLandline(Helper::converToStringValue($rawAttributes, Entity::CONTACT_LANDLINE));
        $detailEntity->setBusinessType(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_TYPE));
        $detailEntity->setBusinessName(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_NAME));
        $detailEntity->setBusinessDescription(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_DESCRIPTION));
        $detailEntity->setBusinessDba(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_DBA));
        $detailEntity->setBusinessWebsite(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_WEBSITE));
        $detailEntity->setAdditionalWebsites(Helper::converToStringValue($rawAttributes, Entity::ADDITIONAL_WEBSITES));
        $detailEntity->setBusinessInternational(Helper::convertBoolToNotNullableInt($rawAttributes, Entity::BUSINESS_INTERNATIONAL));
        $detailEntity->setBusinessPaymentdetails(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_PAYMENTDETAILS));
        $detailEntity->setBusinessRegisteredAddress(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_REGISTERED_ADDRESS));
        $detailEntity->setBusinessRegisteredAddressL2(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_REGISTERED_ADDRESS_L2));
        $detailEntity->setBusinessRegisteredState(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_REGISTERED_STATE));
        $detailEntity->setBusinessRegisteredCity(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_REGISTERED_CITY));
        $detailEntity->setBusinessRegisteredDistrict(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_REGISTERED_DISTRICT));
        $detailEntity->setBusinessRegisteredPin(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_REGISTERED_PIN));
        $detailEntity->setBusinessRegisteredCountry(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_REGISTERED_COUNTRY));
        $detailEntity->setBusinessOperationAddress(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_OPERATION_ADDRESS));
        $detailEntity->setBusinessOperationAddressL2(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_OPERATION_ADDRESS_L2));
        $detailEntity->setBusinessOperationState(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_OPERATION_STATE));
        $detailEntity->setBusinessOperationCity(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_OPERATION_CITY));
        $detailEntity->setBusinessOperationDistrict(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_OPERATION_DISTRICT));
        $detailEntity->setBusinessOperationPin(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_OPERATION_PIN));
        $detailEntity->setBusinessOperationCountry(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_OPERATION_COUNTRY));
        $detailEntity->setBusinessDoe(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_DOE));
        $detailEntity->setGstin(Helper::converToStringValue($rawAttributes, Entity::GSTIN));
        $detailEntity->setPGstin(Helper::converToStringValue($rawAttributes, Entity::P_GSTIN));
        $detailEntity->setCompanyCin(Helper::converToStringValue($rawAttributes, Entity::COMPANY_CIN));
        $detailEntity->setCompanyPan(Helper::converToStringValue($rawAttributes, Entity::COMPANY_PAN));
        $detailEntity->setCompanyPanName(Helper::converToStringValue($rawAttributes, Entity::COMPANY_PAN_NAME));
        $detailEntity->setBusinessCategory(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_CATEGORY));
        $detailEntity->setBusinessSubcategory(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_SUBCATEGORY));
        $detailEntity->setBusinessModel(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_MODEL));
        $detailEntity->setTransactionVolume(Helper::convertToUInt32Value($rawAttributes, Entity::TRANSACTION_VOLUME));
        $detailEntity->setTransactionValue(Helper::convertToUInt32Value($rawAttributes, Entity::TRANSACTION_VALUE));
        $detailEntity->setPromoterPan(Helper::converToStringValue($rawAttributes, Entity::PROMOTER_PAN));
        $detailEntity->setPromoterPanName(Helper::converToStringValue($rawAttributes, Entity::PROMOTER_PAN_NAME));
        $detailEntity->setDateOfBirth(Helper::converToStringValue($rawAttributes, Entity::DATE_OF_BIRTH));
        $detailEntity->setBankName(Helper::converToStringValue($rawAttributes, Entity::BANK_NAME));
        $detailEntity->setBankAccountNumber(Helper::converToStringValue($rawAttributes, Entity::BANK_ACCOUNT_NUMBER));
        $detailEntity->setBankAccountName(Helper::converToStringValue($rawAttributes, Entity::BANK_ACCOUNT_NAME));
        $detailEntity->setBankAccountType(Helper::converToStringValue($rawAttributes, Entity::BANK_ACCOUNT_TYPE));
        $detailEntity->setBankBranch(Helper::converToStringValue($rawAttributes, Entity::BANK_BRANCH));
        $detailEntity->setBankBranchIfsc(Helper::converToStringValue($rawAttributes, Entity::BANK_BRANCH_IFSC));
        $detailEntity->setBankBeneficiaryAddress1(Helper::converToStringValue($rawAttributes, Entity::BANK_BENEFICIARY_ADDRESS1));
        $detailEntity->setBankBeneficiaryAddress2(Helper::converToStringValue($rawAttributes, Entity::BANK_BENEFICIARY_ADDRESS2));
        $detailEntity->setBankBeneficiaryAddress3(Helper::converToStringValue($rawAttributes, Entity::BANK_BENEFICIARY_ADDRESS3));
        $detailEntity->setBankBeneficiaryCity(Helper::converToStringValue($rawAttributes, Entity::BANK_BENEFICIARY_CITY));
        $detailEntity->setBankBeneficiaryState(Helper::converToStringValue($rawAttributes, Entity::BANK_BENEFICIARY_STATE));
        $detailEntity->setBankBeneficiaryPin(Helper::converToStringValue($rawAttributes, Entity::BANK_BENEFICIARY_PIN));
        $detailEntity->setWebsiteAbout(Helper::converToStringValue($rawAttributes, Entity::WEBSITE_ABOUT));
        $detailEntity->setWebsiteContact(Helper::converToStringValue($rawAttributes, Entity::WEBSITE_CONTACT));
        $detailEntity->setWebsitePrivacy(Helper::converToStringValue($rawAttributes, Entity::WEBSITE_PRIVACY));
        $detailEntity->setWebsiteTerms(Helper::converToStringValue($rawAttributes, Entity::WEBSITE_TERMS));
        $detailEntity->setWebsiteRefund(Helper::converToStringValue($rawAttributes, Entity::WEBSITE_REFUND));
        $detailEntity->setWebsitePricing(Helper::converToStringValue($rawAttributes, Entity::WEBSITE_PRICING));
        $detailEntity->setWebsiteLogin(Helper::converToStringValue($rawAttributes, Entity::WEBSITE_LOGIN));
        $detailEntity->setBusinessProofUrl(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_PROOF_URL));
        $detailEntity->setBusinessOperationProofUrl(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_OPERATION_PROOF_URL));
        $detailEntity->setBusinessPanUrl(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_PAN_URL));
        $detailEntity->setAddressProofUrl(Helper::converToStringValue($rawAttributes, Entity::ADDRESS_PROOF_URL));
        $detailEntity->setPromoterProofUrl(Helper::converToStringValue($rawAttributes, Entity::PROMOTER_PROOF_URL));
        $detailEntity->setPromoterPanUrl(Helper::converToStringValue($rawAttributes, Entity::PROMOTER_PAN_URL));
        $detailEntity->setPromoterAddressUrl(Helper::converToStringValue($rawAttributes, Entity::PROMOTER_ADDRESS_URL));
        $detailEntity->setForm12aUrl(Helper::converToStringValue($rawAttributes, Entity::FORM_12A_URL));
        $detailEntity->setForm80gUrl(Helper::converToStringValue($rawAttributes, Entity::FORM_80G_URL));
        $detailEntity->setTransactionReportEmail(Helper::converToStringValue($rawAttributes, Entity::TRANSACTION_REPORT_EMAIL));
        $detailEntity->setRole(Helper::converToStringValue($rawAttributes, Entity::ROLE));
        $detailEntity->setDepartment(Helper::converToStringValue($rawAttributes, Entity::DEPARTMENT));
        $detailEntity->setComment(Helper::converToStringValue($rawAttributes, Entity::COMMENT));
        $detailEntity->setStepsFinished(Helper::convertToStringValueOrDefault($rawAttributes, Entity::STEPS_FINISHED, "[]"));
        $detailEntity->setActivationProgress(Helper::convertToInt32Value($rawAttributes, Entity::ACTIVATION_PROGRESS));
        $detailEntity->setLocked(Helper::convertBoolToNotNullableInt($rawAttributes, Entity::LOCKED));
        $detailEntity->setActivationStatus(Helper::converToStringValue($rawAttributes, Entity::ACTIVATION_STATUS));
        $detailEntity->setPoiVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::POI_VERIFICATION_STATUS));
        $detailEntity->setPoaVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::POA_VERIFICATION_STATUS));
        $detailEntity->setBankDetailsVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::BANK_DETAILS_VERIFICATION_STATUS));
        $detailEntity->setActivationFlow(Helper::converToStringValue($rawAttributes, Entity::ACTIVATION_FLOW));
        $detailEntity->setInternationalActivationFlow(Helper::converToStringValue($rawAttributes, Entity::INTERNATIONAL_ACTIVATION_FLOW));
        $detailEntity->setLiveTransactionDone(Helper::convertToInt32ValueOrDefault($rawAttributes, Entity::LIVE_TRANSACTION_DONE));
        $detailEntity->setClarificationMode(Helper::converToStringValue($rawAttributes, Entity::CLARIFICATION_MODE));
        $detailEntity->setKycClarificationReasons(Helper::converToStringValue($rawAttributes, Entity::KYC_CLARIFICATION_REASONS));
        $detailEntity->setKycAdditionalDetails(Helper::converToStringValue($rawAttributes, Entity::KYC_ADDITIONAL_DETAILS));
        $detailEntity->setKycId(Helper::converToStringValue($rawAttributes, Entity::KYC_ID));
        $detailEntity->setArchivedAt(Helper::convertToInt32Value($rawAttributes, Entity::ARCHIVED_AT));
        $detailEntity->setReviewerId(Helper::converToStringValue($rawAttributes, Entity::REVIEWER_ID));
        $detailEntity->setIssueFields(Helper::converToStringValue($rawAttributes, Entity::ISSUE_FIELDS));
        $detailEntity->setIssueFieldsReason(Helper::converToStringValue($rawAttributes, Entity::ISSUE_FIELDS_REASON));
        $detailEntity->setInternalNotes(Helper::converToStringValue($rawAttributes, Entity::INTERNAL_NOTES));
        $detailEntity->setCustomFields(Helper::converToStringValue($rawAttributes, Entity::CUSTOM_FIELDS));
        $detailEntity->setMarketplaceActivationStatus(Helper::converToStringValue($rawAttributes, Entity::MARKETPLACE_ACTIVATION_STATUS));
        $detailEntity->setVirtualAccountsActivationStatus(Helper::converToStringValue($rawAttributes, Entity::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS));
        $detailEntity->setSubscriptionsActivationStatus(Helper::converToStringValue($rawAttributes, Entity::SUBSCRIPTIONS_ACTIVATION_STATUS));
        $detailEntity->setSubmitted(Helper::convertBoolToNotNullableInt($rawAttributes, Entity::SUBMITTED));
        $detailEntity->setSubmittedAt(Helper::convertToInt32Value($rawAttributes, Entity::SUBMITTED_AT));
        $detailEntity->setEstdYear(Helper::convertToInt32Value($rawAttributes, Entity::ESTD_YEAR));
        $detailEntity->setAuthorizedSignatoryResidentialAddress(Helper::converToStringValue($rawAttributes, Entity::AUTHORIZED_SIGNATORY_RESIDENTIAL_ADDRESS));
        $detailEntity->setAuthorizedSignatoryDob(Helper::converToStringValue($rawAttributes, Entity::AUTHORIZED_SIGNATORY_DOB));
        $detailEntity->setPlatform(Helper::converToStringValue($rawAttributes, Entity::PLATFORM));
        $detailEntity->setFundAccountValidationId(Helper::converToStringValue($rawAttributes, Entity::FUND_ACCOUNT_VALIDATION_ID));
        $detailEntity->setDateOfEstablishment(Helper::converToStringValue($rawAttributes, Entity::DATE_OF_ESTABLISHMENT));
        $detailEntity->setPennyTestingUpdatedAt(Helper::convertToInt32Value($rawAttributes, Entity::PENNY_TESTING_UPDATED_AT));
        $detailEntity->setCompanyPanVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::COMPANY_PAN_VERIFICATION_STATUS));
        $detailEntity->setGstinVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::GSTIN_VERIFICATION_STATUS));
        $detailEntity->setCinVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::CIN_VERIFICATION_STATUS));
        $detailEntity->setPersonalPanDocVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS));
        $detailEntity->setCompanyPanDocVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS));
        $detailEntity->setBankDetailsDocVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS));
        $detailEntity->setShopEstablishmentNumber(Helper::converToStringValue($rawAttributes, Entity::SHOP_ESTABLISHMENT_NUMBER));
        $detailEntity->setShopEstablishmentVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS));
        $detailEntity->setClientApplications(Helper::converToStringValue($rawAttributes, Entity::CLIENT_APPLICATIONS));
        $detailEntity->setOnboardingMilestone(Helper::converToStringValue($rawAttributes,MerchantDetailConstant::ONBOARDING_MILESTONE)); // not present in entity
        $detailEntity->setBusinessSuggestedPin(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_SUGGESTED_PIN));
        $detailEntity->setBusinessSuggestedAddress(Helper::converToStringValue($rawAttributes, Entity::BUSINESS_SUGGESTED_ADDRESS));
        $detailEntity->setFraudType(Helper::converToStringValue($rawAttributes, Entity::FRAUD_TYPE));
        $detailEntity->setBasBusinessId(Helper::converToStringValue($rawAttributes, Entity::BAS_BUSINESS_ID));
        $detailEntity->setMsmeDocVerificationStatus(Helper::converToStringValue($rawAttributes, Entity::MSME_DOC_VERIFICATION_STATUS));
        $detailEntity->setActivationFormMilestone(Helper::converToStringValue($rawAttributes, Entity::ACTIVATION_FORM_MILESTONE));
        $detailEntity->setFundAdditionVaIds(Helper::converToStringValue($rawAttributes, Entity::FUND_ADDITION_VA_IDS));
        $detailEntity->setIecCode(Helper::converToStringValue($rawAttributes, Entity::IEC_CODE));
        $detailEntity->setAuditId(Helper::converToStringValue($rawAttributes, Entity::AUDIT_ID));
        $detailEntity->setBankBranchCodeType(Helper::converToStringValue($rawAttributes, Entity::BANK_BRANCH_CODE_TYPE));
        $detailEntity->setBankBranchCode(Helper::converToStringValue($rawAttributes, Entity::BANK_BRANCH_CODE));
        $detailEntity->setIndustryCategoryCode(Helper::converToStringValue($rawAttributes, Entity::INDUSTRY_CATEGORY_CODE));
        $detailEntity->setIndustryCategoryCodeType(Helper::converToStringValue($rawAttributes, Entity::INDUSTRY_CATEGORY_CODE_TYPE));

        $merchantDetailSaveRequest = new MerchantV1\MerchantDetailSaveRequest();
        $merchantDetailSaveRequest->setMerchantDetail($detailEntity);
        $merchantDetailSaveRequest->setFields($this->dirtyFieldKeys);
        $saveRequest->setMerchantDetailSaveRequest($merchantDetailSaveRequest);
        return $saveRequest;
    }
}
