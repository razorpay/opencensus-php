<?php

namespace RZP\Models\Batch;

class Type
{
    const REFUND           = 'refund';
    const PAYMENT_LINK     = 'payment_link';

    const IRCTC            = 'irctc';

    // IRCTC Batch Types
    const REFUND_IRCTC     = 'refund_irctc';
    const SETTLEMENT_IRCTC = 'settlement_irctc';

    const MERCHANT_BATCH_TYPE = [
        self::IRCTC => [
            self::REFUND_IRCTC      => 'refund_',
            self::SETTLEMENT_IRCTC  => 'settlement_'
        ]
    ];

    /**
     * Following batch types get processed via CRON job, CRON currently runs
     * less frequently (now every 6 hrs).
     */
    const CRON_GROUP = [
        self::REFUND,
    ];

    /**
     * Following batch types get processed via QUEUE, Queues are instant and
     * batch gets processed immediately.
     */
    const QUEUE_GROUP = [
        self::PAYMENT_LINK,
        self::REFUND_IRCTC,
        self::SETTLEMENT_IRCTC
    ];

    public static function exists(string $type)
    {
        return defined(get_class() . '::' . strtoupper($type));
    }

    public static function isQueueGroup(string $type): bool
    {
        return in_array($type, self::QUEUE_GROUP, true);
    }

    public static function getMerchantBatchType(string $merchant, string $filename)
    {
        $type = null;

        if (isset(self::MERCHANT_BATCH_TYPE[$merchant]) === true)
        {
            $type = key(array_filter(
                self::MERCHANT_BATCH_TYPE[$merchant],

                function($file) use ($filename)
                {
                    return (strpos($filename, $file) === 0);
                },
                ARRAY_FILTER_USE_BOTH
            ));
        }

        return $type;
    }
}
