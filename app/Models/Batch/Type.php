<?php

namespace RZP\Models\Batch;

class Type
{
    const REFUND           = 'refund';
    const PAYMENT_LINK     = 'payment_link';

    const IRCTC            = 'Irctc';

    // IRCTC Batch Types
    const IRCTC_REFUND     = 'irctc_refund';
    const IRCTC_SETTLEMENT = 'irctc_settlement';

    const BATCH_MERCHANTS = [
        self::IRCTC,
    ];

    const IRCTC_PROCESSORS_MAPPING = [
        'refund_'     => self::IRCTC_REFUND,
        'settlement_' => self::IRCTC_SETTLEMENT,
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
    ];

    public static function exists(string $type)
    {
        return defined(get_class() . '::' . strtoupper($type));
    }

    public static function isQueueGroup(string $type): bool
    {
        return in_array($type, self::QUEUE_GROUP, true);
    }

    public static function getMerchantBatchType(string $merchant, string $fileName)
    {
        $processorMapping = $merchant . '_PROCESSORS_MAPPING';

        foreach (self::$processorMapping as $key => $value)
        {
            if (strpos($fileName, $key) === 0)
            {
                return $value;
            }
        }
    }
}
