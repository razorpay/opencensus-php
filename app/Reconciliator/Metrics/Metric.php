<?php

namespace RZP\Reconciliator\Metrics;

use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Payment\Refund\Entity as RefundEntity;

/**
 * List of metrics in Recon/ module
 */
class Metric
{
    // ======================= METRICS =======================

    // =========== Histograms ===========
    const RECON_PAYMENT_CREATE_TO_RECONCILED_TIME_MINUTES = 'recon_payment_create_to_reconciled_time_minutes.histogram';
    const RECON_REFUND_CREATE_TO_RECONCILED_TIME_MINUTES  = 'recon_refund_create_to_reconciled_time_minutes.histogram';

    // ======================= END METRICS =======================

    // ======================= DIMENSIONS =======================

    const GATEWAY                 = 'gateway';
    const METHOD                  = 'method';
    const GATEWAY_ACQUIRER        = 'gateway_acquirer';

    // ======================= END DIMENSIONS =======================

    /**
     * Gets dimensions for payment metrics depending
     *
     * @param PaymentEntity $payment
     * @param array         $extra
     *
     * @return array
     */
    public static function getPaymentMetricDimensions(PaymentEntity $payment, array $extra = []): array
    {
        $allDimensions = $extra + [
                self::GATEWAY              =>  $payment->getGateway(),
                self::METHOD               =>  $payment->getMethod(),
            ];

        if ($payment->getTerminalId() !== null)
        {
            $allDimensions[self::GATEWAY_ACQUIRER] = $payment->terminal->getGatewayAcquirer();
        }

        return $allDimensions;
    }

    public static function getRefundMetricDimensions(RefundEntity $refund, array $extra = []): array
    {
        $allDimensions = $extra + [
                self::GATEWAY              =>  $refund->getGateway(),
                self::METHOD               =>  $refund->payment->getMethod(),
            ];

        if ($refund->payment->getTerminalId() !== null)
        {
            $allDimensions[self::GATEWAY_ACQUIRER] = $refund->payment->terminal->getGatewayAcquirer();
        }

        return $allDimensions;
    }
}
