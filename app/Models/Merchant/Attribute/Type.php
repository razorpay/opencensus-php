<?php

namespace RZP\Models\Merchant\Attribute;

class Type
{
    // ONBOARDING Types
    const MERCHANT_ONBOARDING_CATEGORY  = 'merchant_onboarding_category';
    const CA_PAGE_VISITED               = 'ca_page_visited';

    // PREFERENCES Types
    const BUSINESS_CATEGORY             = 'business_category';
    const TEAM_SIZE                     = 'team_size';
    const MONTHLY_PAYOUT_COUNT          = 'monthly_payout_count';

    //INTENT Types
    const CURRENT_ACCOUNT               = 'current_account';
    const PAYOUTS                       = 'payouts';
    const PAYOUT_LINKS                  = 'payout_links';
    const TAX_PAYMENTS                  = 'tax_payments';
    const VENDOR_PAYMENTS               = 'vendor_payments';
    const CORPORATE_CARDS               = 'corporate_cards';
    const UNKNOWN                       = 'unknown';

    //SOURCE Types
    const PG                            = 'pg';
    const WEBSITE                       = 'website';
}
