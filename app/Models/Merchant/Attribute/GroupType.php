<?php

namespace RZP\Models\Merchant\Attribute;

class GroupType
{
    const GROUP_TYPE_MAP = [
        Group::X_SIGNUP => [
            Type::CA_PAGE_VISITED,
            Type::CHANNEL,
            Type::SUBCHANNEL,
            Type::REF_WEBSITE,
            Type::FINAL_UTM_SOURCE,
            Type::FINAL_UTM_MEDIUM,
            Type::FINAL_UTM_CAMPAIGN,
            Type::LAST_CLICK_SOURCE_CATEGORY,
        ],

        Group::ONBOARDING => [
            Type::MERCHANT_ONBOARDING_CATEGORY
        ],

        Group::PRODUCTS_ENABLED => [
            Type::X,
            Type::PG,
        ],

        Group::X_MERCHANT_PREFERENCES => [
            Type::BUSINESS_CATEGORY,
            Type::TEAM_SIZE,
            Type::MONTHLY_PAYOUT_COUNT,
            Type::EXPLORE_DASHBOARD_BUTTON_AT_WELCOME_PAGE_CLICKED,
            Type::NFT_PROJECT,
            Type::CA_LINKING_OPT_OUT,
            Type::VA_KYC_STARTED,
            Type::VA_KYC_POST_ACTIVATION_COMPLETED,
            Type::UNDO_PAYOUTS,
            Type::X_SIGNUP_PLATFORM
        ],

        Group::X_MERCHANT_CURRENT_ACCOUNTS => [
            Type::CA_ALLOCATED_BANK,
            Type::CA_PROCEEDED_BANK,
            Type::CA_ONBOARDING_FLOW,
            Type::CA_CAMPAIGN_ID,
            Type::CA_SALES_LED_ALLOCATED_BANK,
            Type::CA_SALES_LED_ICICI_LEAD_TIMESTAMP,
            Type::CLARITY_CONTEXT,
            Type::SKIP_DWT_ELIGIBLE,
            Type::CA_ONBOARDING_SURVEY_COUNT,
            Type::CA_ONBOARDING_STATE_MACHINE,
            Type::CA_ONBOARDING_FASTER_DOC_COLLECTION
        ],

        Group::X_MERCHANT_INTENT => [
            Type::CURRENT_ACCOUNT,
            Type::PAYOUTS,
            Type::PAYOUT_LINKS,
            Type::TAX_PAYMENTS,
            Type::VENDOR_PAYMENTS,
            Type::CORPORATE_CARDS,
            Type::INSTANT_SETTLEMENTS,
            Type::MARKETPLACE_IS,
            Type::DEMO_ONBOARDING,
            Type::CAPITAL_LOC_EMI,
            Type::OTHERS
        ],

        Group::X_MERCHANT_SOURCE => [
            Type::PG,
            Type::WEBSITE
        ],

        Group::X_TRANSACTION_VIEW => [
            Type::ADMIN,
            Type::FINANCE_L1,
            Type::FINANCE_L2,
            Type::FINANCE_L3,
            Type::OPERATIONS,
            Type::VIEW_ONLY,
        ],

        Group::MASTERCARD => [
            Type::REQUESTER_ID,
            Type::MERCHANT_NAME,
        ],

        Group::VISA => [
            Type::REQUESTER_ID,
            Type::MERCHANT_NAME,
        ],
    ];
}
