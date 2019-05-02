<?php

namespace RZP\Models\Batch;

use RZP\Exception;

class Type
{
    const REFUND                    = 'refund';
    const PAYMENT_LINK              = 'payment_link';

    // Merchant Onboarding
    const MERCHANT_ONBOARDING       = 'merchant_onboarding';

    // IRCTC Batch Types
    const IRCTC_REFUND              = 'irctc_refund';
    const IRCTC_SETTLEMENT          = 'irctc_settlement';

    // Marketplace Batch
    const LINKED_ACCOUNT            = 'linked_account';

    // Virtual Account Bulk Creation
    const VIRTUAL_BANK_ACCOUNT      = 'virtual_bank_account';

    // Bank Transfer Bulk Insert
    const BANK_TRANSFER             = 'bank_transfer';

    const RECURRING_CHARGE          = 'recurring_charge';

    const RECONCILIATION            = 'reconciliation';

    const EMANDATE                  = 'emandate';

    const PAYOUT                    = 'payout';

    const SUB_MERCHANT              = 'sub_merchant';

    const DIRECT_DEBIT              = 'direct_debit';

    const ENTITY_MAPPING            = 'entity_mapping';

    const AUTH_LINK                 = 'auth_link';

    const INSTANT_ACTIVATION        = 'instant_activation';

    // Batch Terminal Creation
    const TERMINAL                  = 'terminal';

    const LINKED_ACCOUNT_REVERSAL   = 'linked_account_reversal';

    /**
     * This is for one time migration of OAuth merchants to Pure-Platform
     * type partners. This bypasses oauth authentication by end merchant.
     */
    const OAUTH_MIGRATION_TOKEN = 'oauth_migration_token';

    /**
     * This type is used to create short urls in bulk async using elfin (hence gimli) service via api
     */
    const ELFIN                 = 'elfin';

    const PARTNER_SUBMERCHANTS  = 'partner_submerchants';

    const CONTACT               = 'contact';

    const FUND_ACCOUNT          = 'fund_account';

    public static $disabledTypes = [
        //
        // Removing till auth for this is figured out. Other parts of the code aren't
        // removed, since this may be necessary for the YesBank integration as well.
        //
        self::BANK_TRANSFER,
        // Not exposed for direct use via api/dashbaord. Its processor is internally used by other batch types.
        self::CONTACT,
    ];

    public static $appTypes = [
        self::RECONCILIATION,
        self::EMANDATE,
        self::BANK_TRANSFER,
        self::ENTITY_MAPPING,
        self::TERMINAL,
        self::MERCHANT_ONBOARDING,
        self::SUB_MERCHANT,
    ];

    /**
     * Following batch types get processed via CRON job, CRON currently runs
     * less frequently (now every 6 hrs).
     *
     * @var array
     */
    public static $cronGroup = [
        self::REFUND,
    ];

    /**
     * Following batch types get processed via QUEUE, Queues are instant and
     * batch gets processed immediately.
     *
     * @var array
     */
    public static $queueGroup = [
        self::PAYMENT_LINK,
        self::LINKED_ACCOUNT,
        self::VIRTUAL_BANK_ACCOUNT,
        self::BANK_TRANSFER,
        self::RECONCILIATION,
        self::EMANDATE,
        self::PAYOUT,
        self::SUB_MERCHANT,
        self::DIRECT_DEBIT,
        self::RECURRING_CHARGE,
        self::ELFIN,
        self::OAUTH_MIGRATION_TOKEN,
        self::PARTNER_SUBMERCHANTS,
        self::ENTITY_MAPPING,
        self::AUTH_LINK,
        self::INSTANT_ACTIVATION,
        self::TERMINAL,
        self::CONTACT,
        self::FUND_ACCOUNT,
        self::MERCHANT_ONBOARDING,
        self::LINKED_ACCOUNT_REVERSAL
    ];

    /**
     * Following batch types get processed via Keubernetes Job, this is used for long
     * running batches.
     *
     * @var array
     */
    public static $kubernetesJobGroup = [
        // Do not include PAYOUT, FUND_ACCOUNT & CONTACT because their implementation is not parallel execution ready.
        self::PAYMENT_LINK,
    ];

    public static function exists(string $type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function isDisabled(string $type)
    {
        return (in_array($type, self::$disabledTypes, true) === true);
    }

    public static function validateType(string $type)
    {
        if ((self::exists($type) === false) or
            (self::isDisabled($type) === true))
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }

    public static function validateTypes(array $types)
    {
        foreach ($types as $row)
        {
            self::validateType($row);
        }
    }

    public static function isQueueGroup(string $type): bool
    {
        return in_array($type, self::$queueGroup, true);
    }

    public static function isKubernetesJobGroup(string $type): bool
    {
        return in_array($type, self::$kubernetesJobGroup, true);
    }

    public static function isAppType(string $type): bool
    {
        return in_array($type, self::$appTypes, true);
    }
}
