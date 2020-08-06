<?php

namespace RZP\Models\Merchant\Detail;

class SmsTemplates
{
    const UNREGISTERED_PAYMENTS_ENABLED             = 'sms.onboarding.unregistered.payments_enabled';

    const UNREGISTERED_SETTLEMENTS_ENABLED          = 'sms.onboarding.unregistered.settlements_enabled';

    const REGISTERED_PAYMENTS_ENABLED               = 'sms.onboarding.registered.payments_enabled';

    const REGISTERED_PAYMENTS_SETTLEMENTS_ENABLED   = 'sms.onboarding.registered.settlements_enabled';

    const PENNY_TESTING_FAILURE                     = 'sms.onboarding.penny_test_failure';

    const NEEDS_CLARIFICATION                       = 'sms.onboarding.needs_clarification';

    const ONBOARDING_SOURCE                         = 'api.merchant.onboarding';
}
