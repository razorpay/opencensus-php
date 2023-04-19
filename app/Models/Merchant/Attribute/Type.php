<?php

namespace RZP\Models\Merchant\Attribute;

class Type
{
    // ONBOARDING Types
    const MERCHANT_ONBOARDING_CATEGORY = 'merchant_onboarding_category';
    const CA_PAGE_VISITED              = 'ca_page_visited';
    const CAMPAIGN_TYPE                = 'campaign_type';

    // X Channel Definition types, used during signup
    const CHANNEL                    = 'channel';
    const SUBCHANNEL                 = 'subchannel';
    const REF_WEBSITE                = 'ref_website';
    const FINAL_UTM_SOURCE           = 'final_utm_source';
    const FINAL_UTM_MEDIUM           = 'final_utm_medium';
    const FINAL_UTM_CAMPAIGN         = 'final_utm_campaign';
    const LAST_CLICK_SOURCE_CATEGORY = 'last_click_source_category';

    // PREFERENCES Types
    const BUSINESS_CATEGORY                                = 'business_category';
    const TEAM_SIZE                                        = 'team_size';
    const MONTHLY_PAYOUT_COUNT                             = 'monthly_payout_count';
    const EXPLORE_DASHBOARD_BUTTON_AT_WELCOME_PAGE_CLICKED = 'explore_dashboard_button_at_welcome_page_clicked';
    const NFT_PROJECT                                      = 'nft_project'; // used for checking if merchant has received their NFT
    const CA_LINKING_OPT_OUT                               = 'ca_linking_opt_out';
    const VA_KYC_STARTED                                   = 'va_kyc_started';
    const VA_KYC_POST_ACTIVATION_COMPLETED                 = 'va_kyc_post_activation_completed';
    const UNDO_PAYOUTS                                     = 'undo_payouts';
    const X_SIGNUP_PLATFORM                                = 'x_signup_platform';

    // CA Account Status Types
    const CA_ALLOCATED_BANK             = 'ca_allocated_bank';
    const CA_PROCEEDED_BANK             = 'ca_proceeded_bank';
    const CA_ONBOARDING_FLOW            = 'ca_onboarding_flow';
    const CA_CAMPAIGN_ID                = 'ca_campaign_id';
    const CA_SALES_LED_ALLOCATED_BANK   = 'ca_sales_led_allocated_bank';
    const CA_SALES_LED_ICICI_LEAD_TIMESTAMP = 'ca_sales_led_icici_lead_timestamp';
    const CLARITY_CONTEXT               = 'clarity_context'; // Valid values - enabled,completed
    const SKIP_DWT_ELIGIBLE             = 'skip_dwt_eligible';
    const CA_ONBOARDING_SURVEY_COUNT    = 'ca_onboarding_survey_count';
    const CA_ONBOARDING_STATE_MACHINE   = 'ca_onboarding_state_machine'; // Valid values = new,old
    const CA_ONBOARDING_FASTER_DOC_COLLECTION = 'ca_onboarding_faster_doc_collection'; // Valid values - active,inactive

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
    const CAPITAL_LOC_EMI               = 'capital_loc_emi';
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

    // CA Onboarding flow values
    const SALES_LED                     = 'SALES_LED';
    const ONE_CA                        = 'ONE_CA';

    //Network Types
    const REQUESTER_ID                    = 'requester_id';
    const MERCHANT_NAME                   = 'merchant_name';

    const X = 'X';
}
