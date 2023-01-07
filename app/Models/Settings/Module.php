<?php

namespace RZP\Models\Settings;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Module
 *
 * Defines modules that settings can be defined for,
 * and related validations
 *
 * @package RZP\Models\Settings
 */
class Module
{
    const USER                   = 'user';
    const BATCH                  = 'batch';
    const PARTNER                = 'partner';
    const MERCHANT               = 'merchant';
    const ONBOARDING             = 'onboarding';
    const OPENWALLET             = 'openwallet';
    const SUBSCRIPTIONS          = 'subscriptions';
    const PAYMENT_LINK           = 'payment_link';
    const PAYMENT_PAGE_ITEM      = 'payment_page_item';
    const PAYOUT_PURPOSE         = 'payout_purpose';
    const PAYOUT_LINK            = 'payout_link';
    const PAYOUTS                = 'payouts';
    const TAX_PAYMENTS           = 'tax_payments';
    const ACC_INTEGRATION        = 'acc_integration';
    const X_APPS                 = 'x_apps';
    const CONTACT_TYPE           = 'contact_type';
    const D2C_BUREAU_CAMPAIGN    = 'd2c_bureau_campaign';
    const BALANCE                = 'balance';
    const FREE_PAYOUT            = 'free_payout';
    const PAYOUT_AMOUNT_TYPE     = 'payout_amount_type';
    const VIRTUAL_ACCOUNT        = 'virtual_account';
    const M2P_TRANSFER           = 'm2p_transfer';
    const QR_CODE                = 'qr_code';
    const PAYMENT_STORE          = 'payment_store';
    const FRAUD_NOTIFY_BATCH     = 'fraud_notify_batch';

    const PAYMENT_LINK_COMPUTED = 'payment_link_computed';

    const IP_WHITELIST_CONFIG  = 'ip_whitelist_config';


    /**
     * @param string $module
     *
     * @return bool
     */
    public static function isValid(string $module): bool
    {
        $const = __CLASS__ . '::' . strtoupper($module);

        return ((defined($const) === true) and (constant($const) === $module));
    }

    /**
     * @param string $module
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validate(string $module)
    {
        if (self::isValid($module) === false)
        {
            throw new BadRequestValidationFailureException(
                'The module specified is invalid',
                'module',
                ['module' => $module]);
        }
    }
}
