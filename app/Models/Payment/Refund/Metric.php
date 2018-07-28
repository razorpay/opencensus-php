<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Payment\Refund\Entity as RefundEntity;

/**
 * List of metrics in Refund/ module
 */
final class Metric
{
    // Counters
    const REFUND_TOTAL_CREATED                          = 'refund_total_created';
    const REFUND_TOTAL_CREATED_FOR_CAPTURED             = 'refund_total_created_for_captured';
    const REFUND_TOTAL_AUTO_INITIATED                   = 'refund_total_auto_initiated';
    const REFUND_TOTAL_PROCESSED                        = 'refund_total_processed';
    const REFUND_TOTAL_FAILED                           = 'refund_total_failed';

    // Histograms
    const REFUND_PROCESS_TIME_FROM_CREATE               = 'refund_process_time_from_create';
    const REFUND_PROCESS_TIME_FROM_LAST_FAILED_ATTEMPT  = 'refund_process_time_from_last_failed_attempt';
    const REFUND_CREATION_TIME_FROM_CAPTURE             = 'refund_creation_time_from_capture';
    const REFUND_CREATION_TIME_FROM_AUTHORIZATION       = 'refund_creation_time_from_authorization';


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
