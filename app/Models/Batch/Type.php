<?php

namespace RZP\Models\Batch;

use RZP\Exception;

class Type
{
    const REFUND                = 'refund';
    const PAYMENT_LINK          = 'payment_link';

    // IRCTC Batch Types
    const IRCTC_REFUND          = 'irctc_refund';
    const IRCTC_SETTLEMENT      = 'irctc_settlement';

    // Marketplace Batch
    const LINKED_ACCOUNT        = 'linked_account';

    // Virtual Account Bulk Creation
    const VIRTUAL_BANK_ACCOUNT  = 'virtual_bank_account';

    // Bank Transfer Bulk Insert
    const BANK_TRANSFER         = 'bank_transfer';

    const RECURRING_CHARGE      = 'recurring_charge';

    const RECONCILIATION        = 'reconciliation';

    const EMANDATE              = 'emandate';

    const PAYOUT                = 'payout';

    const SUB_MERCHANT          = 'sub_merchant';

    const DIRECT_DEBIT          = 'direct_debit';

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

    public static $disabledTypes = [
        //
        // Removing till auth for this is figured out. Other parts of the code aren't
        // removed, since this may be necessary for the YesBank integration as well.
        //
        self::BANK_TRANSFER,
    ];

    public static $appTypes = [
        self::RECONCILIATION,
        self::EMANDATE,
        self::BANK_TRANSFER,
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

    public static function isQueueGroup(string $type): bool
    {
        return in_array($type, self::$queueGroup, true);
    }

    public static function isAppType(string $type): bool
    {
        return in_array($type, self::$appTypes, true);
    }
}
