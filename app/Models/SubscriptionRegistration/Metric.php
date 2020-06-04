<?php

namespace RZP\Models\SubscriptionRegistration;

/**
 * List of metrics for SubscriptionRegistration
 */
final class Metric
{
    // Counters
    const SUBSCRIPTION_REGISTRATION_MIGRATED       = 'subscription_registration_migrated';
    const SUBSCRIPTION_REGISTRATION_CREATED        = 'subscription_registration_created';
    const SUBSCRIPTION_REGISTRATION_AUTHENTICATED  = 'subscription_registration_authenticated';

    const SUBSCRIPTION_REGISTRATION_AUTO_ORDER_CREATED      = 'subscription_registration_auto_order_created';
    const SUBSCRIPTION_REGISTRATION_TOKEN_ASSOCIATED        = 'subscription_registration_token_associated';
    const SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_SUCCESSFUL = 'subscription_registration_auto_payment_successful';
    const SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_FAILED     = 'subscription_registration_auto_payment_failed';
}
