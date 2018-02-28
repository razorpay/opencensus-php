<?php

namespace RZP\Models\Batch;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Exception\BadRequestException;

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

    const RECONCILIATION        = 'reconciliation';

    const EMANDATE              = 'emandate';

    const PAYOUT                = 'payout';

    const SUB_MERCHANT          = 'sub_merchant';

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
    ];

    public static $statsSupportedGroup = [
        self::PAYMENT_LINK,
    ];

    public static function exists(string $type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function validateType(string $type)
    {
        if (self::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }

    public static function isQueueGroup(string $type): bool
    {
        return in_array($type, self::$queueGroup, true);
    }

    public static function isStatsSupportedForType(string $type): bool
    {
        return in_array($type, self::$statsSupportedGroup, true);
    }

    public static function getStatsCoreForType(string $type): Base\Core
    {
        if (self::isStatsSupportedForType($type) === false)
        {
            throw new BadRequestException(
                BAD_REQUEST_BATCH_STATS_NOT_SUPPORTED_FOR_TYPE,
                Entity::TYPE,
                [Entity::TYPE => $type]
            );
        }

        switch ($type)
        {
            case Type::PAYMENT_LINK:
                return (new Invoice\Core);
        }
    }
}
