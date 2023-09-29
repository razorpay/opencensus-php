<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Constants as MerchantConstant;

class Merchant
{
    protected MerchantV1\Merchant $proto;

    function __construct(MerchantV1\Merchant $proto)
    {
        $this->proto = $proto;
    }

    public function ToEntity(): MerchantEntity
    {
        $merchantRawAttributes = [
            MerchantEntity::ID => $this->proto->getId(),
            MerchantEntity::NAME => $this->proto->getNameUnwrapped(),
            MerchantEntity::EMAIL => $this->proto->getEmailUnwrapped(),
            MerchantEntity::SECOND_FACTOR_AUTH => $this->proto->getSecondFactorAuth(),
            MerchantEntity::RESTRICTED => $this->proto->getRestricted(),
            MerchantEntity::PARENT_ID => $this->proto->getParentIdUnwrapped(),
            MerchantEntity::LEGAL_ENTITY_ID => $this->proto->getLegalEntityIdUnwrapped(),
            MerchantEntity::ACTIVATED => $this->proto->getActivated(),
            MerchantEntity::ACTIVATED_AT => $this->proto->getActivatedAtUnwrapped(),
            MerchantEntity::ARCHIVED_AT => $this->proto->getArchivedAtUnwrapped(),
            MerchantEntity::SUSPENDED_AT => $this->proto->getSuspendedAtUnwrapped(),
            MerchantEntity::LIVE => $this->proto->getLive(),
            MerchantEntity::LIVE_DISABLE_REASON => $this->proto->getLiveDisableReasonUnwrapped(),
            MerchantEntity::HOLD_FUNDS => $this->proto->getHoldFunds(),
            MerchantEntity::HOLD_FUNDS_REASON => $this->proto->getHoldFundsReasonUnwrapped(),
            MerchantEntity::PRICING_PLAN_ID => $this->proto->getPricingPlanIdUnwrapped(),
            MerchantEntity::WEBSITE => $this->proto->getWebsiteUnwrapped(),
            MerchantEntity::CATEGORY => $this->proto->getCategoryUnwrapped(),
            MerchantEntity::WHITELISTED_IPS_LIVE => $this->proto->getWhitelistedIpsLiveUnwrapped(),
            MerchantEntity::WHITELISTED_IPS_TEST => $this->proto->getWhitelistedIpsTestUnwrapped(),
            MerchantEntity::WHITELISTED_DOMAINS => $this->proto->getWhitelistedDomainsUnwrapped(),
            MerchantEntity::DASHBOARD_WHITELISTED_IPS_LIVE => $this->proto->getDashboardWhitelistedIpsLiveUnwrapped(),
            MerchantEntity::DASHBOARD_WHITELISTED_IPS_TEST => $this->proto->getDashboardWhitelistedIpsTestUnwrapped(),
            MerchantEntity::PARTNERSHIP_URL => $this->proto->getPartnershipUrlUnwrapped(),
            MerchantEntity::CATEGORY2 => $this->proto->getCategory2Unwrapped(),
            MerchantEntity::INVOICE_CODE => $this->proto->getInvoiceCode(),
            MerchantEntity::NOTES => $this->proto->getNotesUnwrapped(),
            MerchantEntity::ORG_ID => $this->proto->getOrgIdUnwrapped(),
            MerchantEntity::INTERNATIONAL => $this->proto->getInternationalUnwrapped(),
            MerchantEntity::BILLING_LABEL => $this->proto->getBillingLabelUnwrapped(),
            MerchantEntity::DISPLAY_NAME => $this->proto->getDisplayNameUnwrapped(),
            MerchantEntity::CHANNEL => $this->proto->getChannelUnwrapped(),
            MerchantConstant::SETTLEMENT_SCHEDULE => $this->proto->getSettlementScheduleUnwrapped(),
            MerchantConstant::SETTLEMENT_SCHEDULE_ID => $this->proto->getSettlementScheduleIdUnwrapped(),
            MerchantEntity::TRANSACTION_REPORT_EMAIL => $this->proto->getTransactionReportEmailUnwrapped(),
            MerchantEntity::FEE_BEARER => $this->proto->getFeeBearer(),
            MerchantEntity::FEE_MODEL => $this->proto->getFeeModel(),
            MerchantEntity::FEE_CREDITS_THRESHOLD => $this->proto->getFeeCreditsThresholdUnwrapped(),
            MerchantEntity::REFUND_SOURCE => $this->proto->getRefundSourceUnwrapped(),
            MerchantEntity::LINKED_ACCOUNT_KYC => $this->proto->getLinkedAccountKyc(),
            MerchantEntity::HAS_KEY_ACCESS => $this->proto->getHasKeyAccess(),
            MerchantEntity::PARTNER_TYPE => $this->proto->getPartnerTypeUnwrapped(),
            MerchantEntity::BRAND_COLOR => $this->proto->getBrandColorUnwrapped(),
            MerchantEntity::HANDLE => $this->proto->getHandleUnwrapped(),
            MerchantEntity::ACTIVATION_SOURCE => $this->proto->getActivationSourceUnwrapped(),
            MerchantEntity::BUSINESS_BANKING => $this->proto->getBusinessBankingUnwrapped(),
            MerchantEntity::AUTO_CAPTURE_LATE_AUTH => $this->proto->getAutoCaptureLateAuth(),
            MerchantEntity::LOGO_URL => $this->proto->getLogoUrlUnwrapped(),
            MerchantEntity::ICON_URL => $this->proto->getIconUrlUnwrapped(),
            MerchantEntity::INVOICE_LABEL_FIELD => $this->proto->getInvoiceLabelFieldUnwrapped(),
            MerchantEntity::RISK_RATING => $this->proto->getRiskRating(),
            MerchantEntity::RISK_THRESHOLD => $this->proto->getRiskThresholdUnwrapped(),
            MerchantEntity::RECEIPT_EMAIL_ENABLED => $this->proto->getReceiptEmailEnabledUnwrapped(),
            MerchantEntity::RECEIPT_EMAIL_TRIGGER_EVENT => $this->proto->getReceiptEmailTriggerEventUnwrapped(),
            MerchantEntity::MAX_PAYMENT_AMOUNT => $this->proto->getMaxPaymentAmountUnwrapped(),
            MerchantEntity::AUTO_REFUND_DELAY => $this->proto->getAutoRefundDelayUnwrapped(),
            MerchantEntity::DEFAULT_REFUND_SPEED => $this->proto->getDefaultRefundSpeed(),
            MerchantEntity::CONVERT_CURRENCY => $this->proto->getConvertCurrencyUnwrapped(),
            MerchantEntity::CREATED_AT => $this->proto->getCreatedAt(),
            MerchantEntity::UPDATED_AT => $this->proto->getUpdatedAt(),
            MerchantEntity::EXTERNAL_ID => $this->proto->getExternalIdUnwrapped(),
            MerchantEntity::PRODUCT_INTERNATIONAL => $this->proto->getProductInternationalUnwrapped(),
            MerchantConstant::FREE_PAYOUTS_CONSUMED => $this->proto->getFreePayoutsConsumedUnwrapped(),
            MerchantConstant::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => $this->proto->getFreePayoutsConsumedLastResetAtUnwrapped(),
            MerchantEntity::SIGNUP_SOURCE => $this->proto->getSignupSourceUnwrapped(),
            MerchantEntity::ACCOUNT_CODE => $this->proto->getAccountCodeUnwrapped(),
            MerchantEntity::REFUND_CREDITS_THRESHOLD => $this->proto->getRefundCreditsThresholdUnwrapped(),
            MerchantEntity::AMOUNT_CREDITS_THRESHOLD => $this->proto->getAmountCreditsThresholdUnwrapped(),
            MerchantEntity::PURPOSE_CODE => $this->proto->getPurposeCodeUnwrapped(),
            MerchantConstant::FETCH_COUPONS_URL => $this->proto->getFetchCouponsUrlUnwrapped(),
            MerchantConstant::COUPON_VALIDITY_URL => $this->proto->getCouponValidityUrlUnwrapped(),
            MerchantEntity::SIGNUP_VIA_EMAIL => $this->proto->getSignupViaEmail(),
            MerchantEntity::BALANCE_THRESHOLD => $this->proto->getBalanceThresholdUnwrapped(),
            MerchantEntity::MAX_INTERNATIONAL_PAYMENT_AMOUNT => $this->proto->getMaxInternationalPaymentAmountUnwrapped(),
            MerchantEntity::AUDIT_ID => $this->proto->getAuditIdUnwrapped(),
            MerchantEntity::COUNTRY_CODE => $this->proto->getCountryCodeUnwrapped(),
        ];

        $merchantEntity = new MerchantEntity();
        $merchantEntity->setRawAttributes($merchantRawAttributes, true);
        $merchantEntity->exists = true;
        return $merchantEntity;
    }
}
