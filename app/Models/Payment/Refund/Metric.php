<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Payment\Refund\Entity as RefundEntity;

/**
 * List of metrics in Refund/ module
 */
final class Metric
{
    // Counters
    const REFUND_CREATED_TOTAL                              = 'refund_created_total';
    const REFUND_FAILED_TOTAL                               = 'refund_failed_total';

    // Histograms
    const REFUND_PROCESSED_FROM_CREATED_MINUTES             = 'refund_processed_from_created_minutes.histogram';
    const REFUND_PROCESSED_FROM_LAST_FAILED_ATTEMPT_MINUTES = 'refund_processed_from_last_failed_attempt_minutes.histogram';
    const REFUND_CREATED_FROM_CAPTURED_MINUTES              = 'refund_created_from_captured_minutes.histogram';
    const REFUND_CREATED_FROM_AUTHORIZED_MINUTES            = 'refund_created_from_authorized_minutes.histogram';
    const REFUND_CREATION_TIME_FOR_BATCH_MINUTES            = 'refund_creation_time_for_batch_minutes.histogram';


    /**
     * Gets dimensions for metrics around refund module
     * @param Entity $refund
     * @param  array $extra Additional key, value pair of dimensions
     * @return array
     */
    public static function getDimensions(RefundEntity $refund, array $extra = []): array
    {
        return $extra + [
                'gateway'   => $refund->payment->getGateway(),
                'method'    => $refund->payment->getMethod(),
                'category'  => $refund->merchant->getCategory()
            ];
    }
}
