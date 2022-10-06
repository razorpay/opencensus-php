<?php

namespace RZP\Models\SubscriptionRegistration;

/**
 * List of metrics for SubscriptionRegistration
 */
final class Metric
{
    // Counters
    const AUTH_LINK_PAPER_NACH_CREATED            = 'auth_link_paper_nach_created';
    const AUTH_LINK_CHARGE_TOKEN_INITIATED        = 'auth_link_charge_token_initiated';
    const AUTH_LINK_CHARGE_TOKEN_SUBMITTED        = 'auth_link_charge_token_submitted';
    const AUTH_LINK_MIGRATION_STARTED             = 'auth_link_migration_started';
    const AUTH_LINK_MIGRATION_COMPLETED           = 'auth_link_migration_completed';
    const SUBSCRIPTION_REGISTRATION_CREATED       = 'subscription_registration_created';
    const SUBSCRIPTION_REGISTRATION_AUTHENTICATED = 'subscription_registration_authenticated';
    const INVALID_TOKEN_PER_METHOD                = 'invalid_token_per_method';

    const SUBSCRIPTION_REGISTRATION_AUTO_ORDER_CREATED      = 'subscription_registration_auto_order_created';
    const SUBSCRIPTION_REGISTRATION_TOKEN_ASSOCIATED        = 'subscription_registration_token_associated';
    const SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_SUCCESSFUL = 'subscription_registration_auto_payment_successful';
    const SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_FAILED     = 'subscription_registration_auto_payment_failed';

    const SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED       = 'subscription_registration_validation_failed';
    const SUBSCRIPTION_REGISTRATION_AUTHENTICATION_FAILED   = 'subscription_registration_authentication_failed';

    const INVOICE_MUTEX_ACQUIRE_FAILED                      = 'invoice_mutex_acquire_failed';
}
