<?php

namespace RZP\Models\Batch;

use RZP\Exception;

class Type
{
    const REFUND           = 'refund';
    const PAYMENT_LINK     = 'payment_link';

    // IRCTC Batch Types
    const IRCTC_REFUND     = 'irctc_refund';
    const IRCTC_SETTLEMENT = 'irctc_settlement';

    // Marketplace Batch
    const LINKED_ACCOUNT   = 'linked_account';

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
}
