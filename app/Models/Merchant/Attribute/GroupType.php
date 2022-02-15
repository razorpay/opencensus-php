<?php

namespace RZP\Models\Merchant\Attribute;

class GroupType
{
    const GROUP_TYPE_MAP = [
        Group::X_SIGNUP => [
            Type::CA_PAGE_VISITED
        ],

        Group::ONBOARDING => [
            Type::MERCHANT_ONBOARDING_CATEGORY
        ],

        Group::X_MERCHANT_PREFERENCES => [
            Type::BUSINESS_CATEGORY,
            Type::TEAM_SIZE,
            Type::MONTHLY_PAYOUT_COUNT,
            Type::EXPLORE_DASHBOARD_BUTTON_AT_WELCOME_PAGE_CLICKED,
            Type::NFT_PROJECT,
        ],

        Group::X_MERCHANT_CURRENT_ACCOUNTS => [
            Type::CA_ALLOCATED_BANK,
            Type::CA_PROCEEDED_BANK,
            Type::CA_ONBOARDING_FLOW,
            Type::CA_CAMPAIGN_ID,
            Type::CA_SALES_LED_ALLOCATED_BANK,
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
        ]
    ];
}
