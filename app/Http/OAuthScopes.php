<?php

namespace RZP\Http;

use RZP\Trace\TraceCode;

class OAuthScopes
{
    //
    // Following default scope gets assigned to any of the routes
    // based on HTTP method they are allowed
    //
    const READ_ONLY  = 'read_only';
    const READ_WRITE = 'read_write';

    const RX_READ_ONLY = 'rx_read_only';
    const RX_READ_WRITE = 'rx_read_write';

    const RAZORPAY_X_SCOPES = [self::RX_READ_ONLY, self::RX_READ_WRITE, self::APPLE_WATCH_READ_WRITE, self::RX_PARTNER_READ_WRITE];

    const TALLY_READ_ONLY  = 'tally_read_only';
    const TALLY_READ_WRITE = 'tally_read_write';

    const APPLE_WATCH_READ_WRITE = 'apple_watch_read_write';

    /**
     * Adding a new scope for payout approval by partner
     * This scope is attached to the following routes currently;
     * payout_approve, payout_reject, payout_fetch_by_id, payout_fetch_multiple, contact_get
     * contact_list, fund_account_get, fund_account_list, transaction_statement_fetch, transaction_statement_fetch_multiple
     */
    const RX_PARTNER_READ_WRITE = 'rx_partner_read_write';

    /**
     * Map of additional scopes for a route (identified by the route name alias)
     * If the token has any one of the scopes for that route, then request is allowed
     *
     * Add each route in the section of corresponding auth routes
     * i.e public routes to public section, private routes to private section etc..
     *
     * @var array
     */
    protected static $scopes = [
        // Just a dummy route, gets used in tests
        'feature_dummy' => ['dummy.read'],

        // public routes
        'contact_get_public'                      => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'payout_links_generate_end_user_otp'      => [self::RX_READ_WRITE],
        'payout_links_verify_customer_otp'        => [self::RX_READ_WRITE],
        'payout_links_customer_hosted_page'       => [self::RX_READ_WRITE],
        'payout_links_added_fund_accounts'        => [self::RX_READ_WRITE],
        'payout_links_initiate'                   => [self::RX_READ_WRITE],
        'payout_links_generate_end_user_otp_cors' => [self::RX_READ_WRITE],
        'payout_links_verify_customer_otp_cors'   => [self::RX_READ_WRITE],
        'payout_links_initiate_cors'              => [self::RX_READ_WRITE],
        'payout_links_added_fund_accounts_cors'   => [self::RX_READ_WRITE],
        'payout_links_status'                     => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'payout_links_status_cors'                => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'payout_links_create_batch'               => [self::RX_READ_WRITE],

        // private routes
        'payout_links_fetch_multiple'                    => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'payout_links_fetch_by_id'                       => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'payout_links_create'                            => [self::RX_READ_WRITE],
        'payout_links_cancel'                            => [self::RX_READ_WRITE],
        'payout_purpose_get'                             => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'payout_purpose_post'                            => [self::RX_READ_WRITE],
        'payout_fetch_by_id'                             => [self::RX_READ_ONLY, self::RX_READ_WRITE, self::RX_PARTNER_READ_WRITE],
        'payout_fetch_multiple'                          => [self::RX_READ_ONLY, self::RX_READ_WRITE, self::APPLE_WATCH_READ_WRITE, self::RX_PARTNER_READ_WRITE],
        'payout_reject'                                  => [ self::RX_READ_WRITE, self::APPLE_WATCH_READ_WRITE, self::RX_PARTNER_READ_WRITE],
        'payout_approve'                                 => [ self::RX_READ_WRITE, self::APPLE_WATCH_READ_WRITE, self::RX_PARTNER_READ_WRITE],
        'payout_2fa_approve'                             => [ self::RX_READ_WRITE, self::APPLE_WATCH_READ_WRITE],
        'user_otp_create'                                => [self::RX_READ_ONLY, self::RX_READ_WRITE, self::APPLE_WATCH_READ_WRITE],
        'activated_banking_accounts_list'                => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'merchant_balance_fetch'                         => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'payout_create'                                  => [self::RX_READ_WRITE],
        'payout_cancel'                                  => [self::RX_READ_WRITE],
        'virtual_account_create_for_banking'             => [self::RX_READ_WRITE],
        'contact_types_get'                              => [self::RX_READ_ONLY, self::RX_READ_WRITE],
        'contact_types_post'                             => [self::RX_READ_WRITE],
        'contact_get'                                    => [self::RX_READ_ONLY, self::RX_READ_WRITE, self::RX_PARTNER_READ_WRITE],
        'contact_list'                                   => [self::RX_READ_ONLY, self::RX_READ_WRITE, self::RX_PARTNER_READ_WRITE],
        'contact_create'                                 => [self::RX_READ_WRITE],
        'contact_update'                                 => [self::RX_READ_WRITE],
        'fund_account_validate'                          => [self::READ_WRITE, self::RX_READ_WRITE],
        'fund_account_validate_fetch'                    => [self::READ_ONLY, self::READ_WRITE, self::RX_READ_ONLY, self::RX_READ_WRITE],
        'fund_account_validate_fetch_by_id'              => [self::READ_ONLY, self::READ_WRITE, self::RX_READ_ONLY, self::RX_READ_WRITE],
        'fund_account_get'                               => [self::READ_ONLY, self::READ_WRITE, self::RX_READ_ONLY, self::RX_READ_WRITE, self::RX_PARTNER_READ_WRITE],
        'fund_account_list'                              => [self::READ_ONLY, self::READ_WRITE, self::RX_READ_ONLY, self::RX_READ_WRITE, self::RX_PARTNER_READ_WRITE],
        'fund_account_create'                            => [self::READ_WRITE, self::RX_READ_WRITE],
        'fund_account_update'                            => [self::READ_WRITE, self::RX_READ_WRITE],
        'merchant_activation_update_partner'             => [],
        'merchant_activation_status_partner'             => [],

        'accounting_integration_tally_cash_flow_acknowledge'    => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_cash_flow_update_mapping' => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_fetch_cash_flow_entries'  => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_ack_bank_transactions'    => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_get_bank_transactions'    => [self::TALLY_READ_WRITE],

        'accounting_integration_tally_invoices'                 => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_fetch_invoice'            => [self::TALLY_READ_ONLY, self::TALLY_READ_WRITE],
        'accounting_integration_tally_cancel_invoice'           => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_fetch_payment'            => [self::TALLY_READ_ONLY, self::TALLY_READ_WRITE],
        'accounting_integration_tally_acknowledge_payment'      => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_integrate'                => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_delete_integration'       => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_create_contact'           => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_sync_status'              => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_tax_slab_rates'           => [self::TALLY_READ_WRITE],
        'accounting_integration_add_or_update_settings'         => [self::TALLY_READ_WRITE],
        'accounting_integration_tally_banking_accounts'         => [self::TALLY_READ_WRITE],
        'accounting_integration_update_rx_tally_ledger_mapping' => [self::TALLY_READ_WRITE],

        'payment_links_get'                      => [self::READ_ONLY, self::READ_WRITE, self::TALLY_READ_ONLY, self::TALLY_READ_WRITE],
        'payment_links_fetch_multiple'           => [self::READ_ONLY, self::READ_WRITE, self::TALLY_READ_ONLY, self::TALLY_READ_WRITE],
        'payment_links_service_count_route'      => [self::READ_ONLY, self::READ_WRITE, self::TALLY_READ_ONLY, self::TALLY_READ_WRITE],
        'payment_links_create'                   => [self::READ_WRITE, self::TALLY_READ_WRITE],
        'payment_links_update'                   => [self::READ_WRITE, self::TALLY_READ_WRITE],
        'payment_links_cancel'                   => [self::READ_WRITE, self::TALLY_READ_WRITE],
        'payment_links_expire'                   => [self::READ_WRITE, self::TALLY_READ_WRITE],

        'user_fetch_self'                        => [self::APPLE_WATCH_READ_WRITE],
        'banking_accounts_list'                  => [self::APPLE_WATCH_READ_WRITE],
        'payouts_summary'                        => [self::APPLE_WATCH_READ_WRITE],
        'transaction_statement_fetch'            => [self::RX_PARTNER_READ_WRITE],
        'transaction_statement_fetch_multiple'   => [self::RX_PARTNER_READ_WRITE]
    ];

    /**
     * Gets array of scopes that a route is mapped to.
     * Tokens will be given access if they have at least one
     * of the route scopes allowed during token creation.
     *
     * @param string $route
     *
     * @return array|mixed
     */
    public static function getScopesForRoute(string $route)
    {
        if (isset(self::$scopes[$route]) === true)
        {
            return self::$scopes[$route];
        }

        $scopes = [];

        self::addDefaultScopesForRoute($scopes, $route);

        return $scopes;
    }

    protected static function addDefaultScopesForRoute(array & $scopes, string $route)
    {
        $routeParams = Route::getApiRoute($route);

        if (empty($scopes) === false)
        {
            return;
        }

        //
        // Adds the default scopes for the current route to existing $scopes
        //
        $scopes[] = self::READ_WRITE;

        if ($routeParams[0] === 'get')
        {
            $scopes[] = self::READ_ONLY;
        }
    }

    public static function getScopes()
    {
        return self::$scopes;
    }

    public static function getOauthScopesByServiceOwner(string $serviceOwner)
    {
        switch ($serviceOwner)
        {
            case 'api-live':
            case 'api-test':
            case 'beta-api-live':
            case 'beta-api-test':
            case 'omega-api-live':
            case 'omega-api-test':
                return [self::READ_ONLY, self::READ_WRITE];

            default:
                return [self::RX_READ_ONLY, self::RX_READ_WRITE];

        }
    }
}
