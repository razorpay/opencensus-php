<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Google\Protobuf\StringValue;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Constants as MerchantConstant;

class Merchant implements EntityToProtoConvertorInterface
{
    protected MerchantEntity $entity;

    use DeleteNotSupported;

    protected array $dirtyFieldKeys;

    function __construct(MerchantEntity $entity, array $dirtyFieldKeys)
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

        $merchantSaveRequest = new MerchantV1\MerchantSaveRequest();

        $merchant = new MerchantV1\Merchant();

        $rawAttributes = $this->entity->getAttributes();
        $merchant->setId(Helper::notNullCheck($rawAttributes, UniqueIdEntity::ID));
        $merchant->setName(Helper::converToStringValue($rawAttributes, MerchantEntity::NAME));
        $merchant->setEmail(Helper::converToStringValue($rawAttributes, MerchantEntity::EMAIL));
        $merchant->setSecondFactorAuth(Helper::convertBoolToNotNullableInt($rawAttributes,MerchantEntity::SECOND_FACTOR_AUTH));
        $merchant->setRestricted(Helper::convertBoolToNotNullableInt($rawAttributes, MerchantEntity::RESTRICTED));
        $merchant->setParentId(Helper::converToStringValue($rawAttributes, MerchantEntity::PARENT_ID));
        $merchant->setLegalEntityId(Helper::converToStringValue($rawAttributes, MerchantEntity::LEGAL_ENTITY_ID));
        $merchant->setActivated(Helper::convertBoolToNotNullableInt($rawAttributes, MerchantEntity::ACTIVATED));
        $merchant->setActivatedAt(Helper::convertToInt32Value($rawAttributes, MerchantEntity::ACTIVATED_AT));
        $merchant->setArchivedAt(Helper::convertToInt32Value($rawAttributes, MerchantEntity::ARCHIVED_AT));
        $merchant->setSuspendedAt(Helper::convertToInt32Value($rawAttributes, MerchantEntity::SUSPENDED_AT));
        $merchant->setLive(Helper::convertBoolToNotNullableInt($rawAttributes, MerchantEntity::LIVE));
        $merchant->setLiveDisableReason(Helper::converToStringValue($rawAttributes, MerchantEntity::LIVE_DISABLE_REASON));
        $merchant->setHoldFunds(Helper::convertBoolToNotNullableInt($rawAttributes, MerchantEntity::HOLD_FUNDS));
        $merchant->setHoldFundsReason(Helper::converToStringValue($rawAttributes, MerchantEntity::HOLD_FUNDS_REASON));
        $merchant->setPricingPlanId(Helper::converToStringValue($rawAttributes, MerchantEntity::PRICING_PLAN_ID));
        $merchant->setWebsite(Helper::converToStringValue($rawAttributes, MerchantEntity::WEBSITE));
        $merchant->setCategory(Helper::converToStringValue($rawAttributes, MerchantEntity::CATEGORY));
        $merchant->setWhitelistedIpsLive(Helper::converToStringValue($rawAttributes, MerchantEntity::WHITELISTED_IPS_LIVE));
        $merchant->setWhitelistedIpsTest(Helper::converToStringValue($rawAttributes, MerchantEntity::WHITELISTED_IPS_TEST));
        // @todo check what does get stored here, should check what gets stored via api and asv
        $merchant->setWhitelistedDomains(Helper::converToStringValue($rawAttributes, MerchantEntity::WHITELISTED_DOMAINS));
        // @todo check what does get stored here, should check what gets stored via api and asv
        $merchant->setDashboardWhitelistedIpsLive(Helper::converToStringValue($rawAttributes, MerchantEntity::DASHBOARD_WHITELISTED_IPS_LIVE));
        // @todo check what does get stored here, should check what gets stored via api and asv
        $merchant->setDashboardWhitelistedIpsTest(Helper::converToStringValue($rawAttributes, MerchantEntity::DASHBOARD_WHITELISTED_IPS_TEST));
        // @todo check what does get stored here, should check what gets stored via api and asv
        $merchant->setPartnershipUrl(Helper::converToStringValue($rawAttributes, MerchantEntity::PARTNERSHIP_URL));
        $merchant->setInvoiceCode(Helper::notNullCheck($rawAttributes, MerchantEntity::INVOICE_CODE));
        $merchant->setCategory2(Helper::converToStringValue($rawAttributes, MerchantEntity::CATEGORY2));
        // @todo check what does get stored here, should check what gets stored via api and asv
        $merchant->setNotes(Helper::converToStringValue($rawAttributes, MerchantEntity::NOTES));
        $merchant->setOrgId(Helper::converToStringValue($rawAttributes, MerchantEntity::ORG_ID));
        $merchant->setInternational(Helper::convertToInt32ValueFromBoolOrDefault($rawAttributes, MerchantEntity::INTERNATIONAL, 0 ));
        $merchant->setBillingLabel(Helper::converToStringValue($rawAttributes, MerchantEntity::BILLING_LABEL));
        $merchant->setDisplayName(Helper::converToStringValue($rawAttributes, MerchantEntity::DISPLAY_NAME));
        // @todo check what does get stored here, should check what gets stored via api and asv
        $merchant->setChannel(Helper::converToStringValue($rawAttributes, MerchantEntity::CHANNEL));
        $merchant->setTransactionReportEmail(Helper::converToStringValue($rawAttributes, MerchantEntity::TRANSACTION_REPORT_EMAIL));
        $merchant->setFeeBearer($rawAttributes[MerchantEntity::FEE_BEARER] ?? 0);
        $merchant->setFeeModel($rawAttributes[MerchantEntity::FEE_MODEL] ?? 0);
        $merchant->setFeeCreditsThreshold(Helper::convertToUInt64Value($rawAttributes, MerchantEntity::FEE_CREDITS_THRESHOLD));
        $merchant->setRefundSource(Helper::convertToInt32ValueOrDefault($rawAttributes, MerchantEntity::REFUND_SOURCE, 0));
        $merchant->setLinkedAccountKyc(Helper::convertBoolToNotNullableInt($rawAttributes,MerchantEntity::LINKED_ACCOUNT_KYC));
        $merchant->setHasKeyAccess(Helper::convertBoolToNotNullableInt($rawAttributes, MerchantEntity::HAS_KEY_ACCESS));
        $merchant->setPartnerType(Helper::converToStringValue($rawAttributes, MerchantEntity::PARTNER_TYPE));
        $merchant->setBrandColor(Helper::converToStringValue($rawAttributes, MerchantEntity::BRAND_COLOR));
        $merchant->setHandle(Helper::converToStringValue($rawAttributes, MerchantEntity::HANDLE));
        $merchant->setActivationSource(Helper::converToStringValue($rawAttributes, MerchantEntity::ACTIVATION_SOURCE));
        $merchant->setBusinessBanking(Helper::convertToInt32ValueOrDefault($rawAttributes, MerchantEntity::BUSINESS_BANKING));
        $merchant->setAutoCaptureLateAuth(Helper::convertBoolToNotNullableInt($rawAttributes, MerchantEntity::AUTO_CAPTURE_LATE_AUTH, 0));
        // @todo check what does get stored here, should check what gets stored via api and asv
        $merchant->setLogoUrl(Helper::converToStringValue($rawAttributes, MerchantEntity::LOGO_URL));
        // @todo check what does get stored here, should check what gets stored via api and asv
        $merchant->setIconUrl(Helper::converToStringValue($rawAttributes, MerchantEntity::ICON_URL));
        $merchant->setInvoiceLabelField(Helper::converToStringValue($rawAttributes, MerchantEntity::INVOICE_LABEL_FIELD));
        $merchant->setRiskRating(helper::notNullCheck($rawAttributes, MerchantEntity::RISK_RATING));
        $merchant->setRiskThreshold(helper::convertToUInt32Value($rawAttributes, MerchantEntity::RISK_THRESHOLD));
        $merchant->setReceiptEmailEnabled(helper::convertToInt32ValueFromBool($rawAttributes, MerchantEntity::RECEIPT_EMAIL_ENABLED));
        $merchant->setReceiptEmailTriggerEvent(helper::convertToUInt32ValueOrDefault($rawAttributes, MerchantEntity::RECEIPT_EMAIL_TRIGGER_EVENT, 1));
        $merchant->setMaxPaymentAmount(helper::convertToUInt64Value($rawAttributes, MerchantEntity::MAX_PAYMENT_AMOUNT));
        $merchant->setAutoRefundDelay(helper::convertToInt32Value($rawAttributes, MerchantEntity::AUTO_REFUND_DELAY));
        $merchant->setDefaultRefundSpeed(helper::convertBoolToNotNullableString($rawAttributes, MerchantEntity::DEFAULT_REFUND_SPEED , 'normal'));
        $merchant->setConvertCurrency(helper::convertToInt32ValueFromBool($rawAttributes, MerchantEntity::CONVERT_CURRENCY));
        $merchant->setExternalId(Helper::converToStringValue($rawAttributes, MerchantEntity::EXTERNAL_ID));
        $merchant->setProductInternational(Helper::convertToStringValueOrDefault($rawAttributes, MerchantEntity::PRODUCT_INTERNATIONAL, "0000000000"));
        $merchant->setSignupSource(Helper::converToStringValue($rawAttributes, MerchantEntity::SIGNUP_SOURCE));
        $merchant->setAccountCode(Helper::converToStringValue($rawAttributes, MerchantEntity::ACCOUNT_CODE));
        $merchant->setRefundCreditsThreshold(Helper::convertToUInt64Value($rawAttributes, MerchantEntity::REFUND_CREDITS_THRESHOLD));
        $merchant->setAmountCreditsThreshold(Helper::convertToUInt64Value($rawAttributes, MerchantEntity::AMOUNT_CREDITS_THRESHOLD));
        $merchant->setPurposeCode(Helper::converToStringValue($rawAttributes, MerchantEntity::PURPOSE_CODE));
        $merchant->setSignupViaEmail(Helper::convertToNotNullableInt($rawAttributes, MerchantEntity::SIGNUP_VIA_EMAIL, 1));
        $merchant->setBalanceThreshold(helper::convertToInt64Value($rawAttributes, MerchantEntity::BALANCE_THRESHOLD));
        $merchant->setMaxInternationalPaymentAmount(helper::convertToUInt64Value($rawAttributes, MerchantEntity::MAX_INTERNATIONAL_PAYMENT_AMOUNT));
        $merchant->setAuditId(helper::converToStringValue($rawAttributes, MerchantEntity::AUDIT_ID));
        $merchant->setCountryCode(helper::convertToStringValueOrDefault($rawAttributes, MerchantEntity::COUNTRY_CODE, "IN"));

        // This fields are not in entity, we are still adding this here.
        $merchant->setFreePayoutsConsumed(helper::convertToInt32ValueOrDefault($rawAttributes, MerchantConstant::FREE_PAYOUTS_CONSUMED,0));
        $merchant->setFreePayoutsConsumedLastResetAt(helper::convertToUInt32Value($rawAttributes, MerchantConstant::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT));
        $merchant->setFetchCouponsUrl(helper::converToStringValue($rawAttributes, MerchantConstant::FETCH_COUPONS_URL));
        $merchant->setCouponValidityUrl(helper::converToStringValue($rawAttributes, MerchantConstant::COUPON_VALIDITY_URL));
        $merchant->setSettlementSchedule(helper::convertToInt32ValueOrDefault($rawAttributes, MerchantConstant::SETTLEMENT_SCHEDULE, 3));
        $merchant->setSettlementScheduleId(helper::converToStringValue($rawAttributes, MerchantConstant::SETTLEMENT_SCHEDULE_ID));

        $merchantSaveRequest->setMerchant($merchant);
        $merchantSaveRequest->setFields($this->dirtyFieldKeys);
        $saveRequest->setMerchantSaveRequest($merchantSaveRequest);

        return $saveRequest;
    }
}
