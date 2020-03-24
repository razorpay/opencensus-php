<?php

namespace RZP\Trace;

use App;
use Request;

class ApiTraceProcessor
{
    protected $app;

    //
    // This regex is copied from
    // https://adamcaudill.com/2011/10/20/masking-credit-cards-for-pci/
    //
    // Sample string: 'CCPAY.4000000000000002@icici'
    //
    const CCPAY_CARD_REGEX = "/CCPAY.(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|" .
                             "6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|" .
                             "[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})/";

    //
    // This regex is taken from https://www.regular-expressions.info/creditcard.html
    // This regex is used to scrub credit card numbers from logs.
    // Currently only banking specific routes will be affected by this
    //
    const CARD_REGEX = "/(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|" .
                       "6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|" .
                       "[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})/";

    //
    // Banking specific routes for which credit card info will be scrubbed from logs
    //
    const BANKING_SPECIFIC_ROUTES = [
        'payout_create',
        'payout_create_with_otp',
        'payout_bulk_create',
        'payout_approve_bulk',
        'payout_reject_bulk',
        'payout_approve',
        'payout_reject',
        'payout_fetch_by_id',
        'payout_fetch_multiple',
        'payout_cancel',
        'payout_update_status',
        'payout_purpose_get',
        'payout_purpose_post',
        'payout_fetch_reversals',
        'payouts_process_queued',
        'payouts_summary',
        'payouts_workflow_summary',

        'payout_links_fetch_multiple',
        'payout_links_fetch_by_id',
        'payout_links_create',
        'payout_links_generate_end_user_otp',
        'payout_links_generate_end_user_otp_cors',
        'payout_links_verify_customer_otp',
        'payout_links_verify_customer_otp_cors',
        'payout_links_cancel',
        'payout_links_status',
        'payout_links_status_cors',
        'payout_update_pull_payout_status',
        'payout_links_customer_hosted_page',

        'payout_links_added_fund_accounts',
        'payout_links_added_fund_accounts_cors',
        'payout_links_initiate',
        'payout_links_initiate_cors',
        'payout_links_settings_post',
        'payout_links_settings_get',
        'payout_links_merchant_settings_get',
        'payout_links_merchant_settings_post',
        'payout_links_merchant_on_boarding_status',
        'payout_links_merchant_summary',
        'payout_links_resend_notification',

        'contact_get',
        'contact_list',
        'contact_create',
        'bulk_contact_create',
        'contact_update',
        'contact_delete',
        'contact_types_get',
        'contact_types_post',

        'fund_account_validate',
        'fund_account_validation_retry',
        'fund_account_validate_fetch',
        'fund_account_validate_fetch_by_id',
        'fund_account_get',
        'fund_account_list',
        'fund_account_create',
        'fund_account_update',
        'fund_account_bulk_create',

        'banking_account_statement_generate',

        'bank_transfer_process',
        'bank_transfer_process_icici',
        'bank_transfer_process_file',
        'bank_transfer_process_file_rbl',
        'bank_transfer_process_rbl',
        'bank_transfer_process_rbl_test',
        'bank_transfer_process_rbl_internal',
        'bank_transfer_process_test',
    ];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function __invoke(array $record)
    {
        $this->addMode($record);

        $this->addOAuthAttributes($record);

        $this->addPartnerAttributes($record);

        $this->updateClientIp($record);

        $this->addMerchantId($record);

        $this->addDashboardHeaders($record);

        $this->addRouteNameForExceptions($record);

        $this->scrubCardNumberViaCcPay($record);

        $this->scrubCardNumberForBankingRoutes($record);

        $this->overrideRequestAttributes($record);

        return $record;
    }

    protected function addMode(& $record)
    {
        $record['mode'] = $this->app['basicauth']->getMode();
    }

    protected function addOAuthAttributes(& $record)
    {
        $accessTokenId = $this->app['basicauth']->getAccessTokenId();

        if (empty($accessTokenId) === false)
        {
            $record['request']['access_token_id'] = $accessTokenId;
        }

        $oauthClientId = $this->app['basicauth']->getOAuthClientId();

        if (empty($oauthClientId) === false)
        {
            $record['request']['oauth_client_id'] = $oauthClientId;
        }
    }

    protected function addPartnerAttributes(& $record)
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        if (empty($partnerMerchantId) === false)
        {
            $record['request']['partner_merchant_id'] = $partnerMerchantId;
        }
    }

    protected function updateClientIp(& $record)
    {
        $record['request']['client_ip'] = $this->app['request']->ip();
    }

    protected function addMerchantId(& $record)
    {
        $record['request']['merchant_id'] = $this->app['basicauth']->getMerchantId();
    }

    protected function addDashboardHeaders(&$record)
    {
        if ($this->app['basicauth']->isDashboardApp() === true)
        {
            $record['request'] += $this->app['basicauth']->getDashboardHeaders();
        }
    }

    protected function addRouteNameForExceptions(& $record)
    {
        // If this is an exception, a stack key is present in the context array
        $stackPresent = isset($record['context']['stack']);

        if ($stackPresent === true)
        {
            $record['request']['route_name'] = optional($this->app['router'])->currentRouteName();
        }
    }

    protected function scrubCardNumberViaCcPay(& $record)
    {
        $context = $record['context'] ?? null;

        if (empty($context) === true)
        {
            return;
        }

        array_walk_recursive($context, function(& $item)
        {
            if (is_string($item) === true)
            {
                if (preg_match(self::CCPAY_CARD_REGEX, $item) === 1)
                {
                    $item = 'CARD_NUMBER_SCRUBBED';
                }
            }
        });

        $record['context'] = $context;
    }

    protected function scrubCardNumberForBankingRoutes(& $record)
    {
        $route = optional($this->app['router'])->currentRouteName();

        if (in_array($route, self::BANKING_SPECIFIC_ROUTES) === false)
        {
            return;
        }

        $context = $record['context'] ?? null;

        if (empty($context) === true)
        {
            return;
        }

        array_walk_recursive($context, function(& $item)
        {
            if (is_string($item) === true)
            {
                if (preg_match(self::CARD_REGEX, $item) === 1)
                {
                    $item = 'CARD_NUMBER_SCRUBBED';
                }
            }
        });

        $record['context'] = $context;
    }

    /**
     * So WebProcessor is pushed with request object available at the time as
     * part of framework's first set of things i.e. registering service
     * providers. After this http middleware are registered where
     * Fideloper\Proxy\TrustProxies (library) verifies and attaches x-forwarded-
     * headers of proxy server. That's it- tiny bad practice/miss causing issues.
     *
     * Refer: Razorpay\Trace\Processor\WebProcessor@getServerData.
     *
     * @param  array &$record
     * @return void
     */
    protected function overrideRequestAttributes(array &$record)
    {
        $record['request']['url'] = $this->app->request->getUri();
    }
}
