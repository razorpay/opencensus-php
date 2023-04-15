<?php

namespace RZP\Models\Merchant\Detail;

class SmsTemplates
{
    const UNREGISTERED_PAYMENTS_ENABLED             = 'sms.onboarding.unregistered.payments_enabled_v2';

    const PROMO_UNREGISTERED_PAYMENTS_ENABLED       = 'sms.onboarding.unregistered.payment_enabled_pr';

    const UNREGISTERED_SETTLEMENTS_ENABLED          = 'sms.onboarding.unregistered.settlements_enabled';

    const PROMO_UNREGISTERED_SETTLEMENTS_ENABLED    = 'sms.onboarding.unregistered.settlement_enabled_pr';

    const REGISTERED_PAYMENTS_ENABLED               = 'sms.onboarding.registered.payments_enabled';

    const PROMO_REGISTERED_PAYMENTS_ENABLED         = 'sms.onboarding.registered.payment_enabled_pr';

    const REGISTERED_SETTLEMENTS_ENABLED            = 'sms.onboarding.registered.settlements_enabled';

    const PROMO_REGISTERED_SETTLEMENTS_ENABLED      = 'sms.onboarding.registered.settlement_enabled_pr';

    const PENNY_TESTING_FAILURE                     = 'sms.onboarding.penny_test_failure';

    const PROMO_PENNY_TESTING_FAILURE               = 'sms.onboarding.penny_test_failure_pr';

    const NEEDS_CLARIFICATION                       = 'sms.onboarding.needs_clarification_v2';

    const PROMO_NEEDS_CLARIFICATION                 = 'sms.onboarding.needs_clarification_pr';

    const ONBOARDING_SOURCE                         = 'api.merchant.onboarding';

}
