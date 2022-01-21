<?php

namespace RZP\Models\Merchant\Attribute;

class Type
{
    // ONBOARDING Types
    const MERCHANT_ONBOARDING_CATEGORY  = 'merchant_onboarding_category';
    const CA_PAGE_VISITED               = 'ca_page_visited';
    const CAMPAIGN_TYPE                 = 'campaign_type';

    // PREFERENCES Types
    const BUSINESS_CATEGORY                                = 'business_category';
    const TEAM_SIZE                                        = 'team_size';
    const MONTHLY_PAYOUT_COUNT                             = 'monthly_payout_count';
    const EXPLORE_DASHBOARD_BUTTON_AT_WELCOME_PAGE_CLICKED = 'explore_dashboard_button_at_welcome_page_clicked';
    const NFT_PROJECT                                      = 'nft_project'; // used for checking if merchant has received their NFT

    // CA Account Status Types
    const CA_ALLOCATED_BANK             = 'ca_allocated_bank';
    const CA_PROCEEDED_BANK             = 'ca_proceeded_bank';
    const CA_ONBOARDING_FLOW            = 'ca_onboarding_flow';
    const CA_CAMPAIGN_ID                = 'ca_campaign_id';

    //INTENT Types
    const CURRENT_ACCOUNT               = 'current_account';
    const PAYOUTS                       = 'payouts';
    const PAYOUT_LINKS                  = 'payout_links';
    const TAX_PAYMENTS                  = 'tax_payments';
    const VENDOR_PAYMENTS               = 'vendor_payments';
    const CORPORATE_CARDS               = 'corporate_cards';
    const INSTANT_SETTLEMENTS           = 'instant_settlements';
    const MARKETPLACE_IS                = 'marketplace_is';
    const DEMO_ONBOARDING               = 'demo_onboarding';
    const OTHERS                        = 'others';

    //SOURCE Types
    const PG                            = 'pg';
    const WEBSITE                       = 'website';

    // Role Types
    const OWNER                         = 'owner';
    const FINANCE_L1                    = 'finance_l1';
    const FINANCE_L2                    = 'finance_l2';
    const FINANCE_L3                    = 'finance_l3';
    const ADMIN                         = 'admin';
    const OPERATIONS                    = 'operations';
    const VIEW_ONLY                     = 'view_only';
    const CHARTERED_ACCOUNTANT          = 'chartered_accountant';

}
