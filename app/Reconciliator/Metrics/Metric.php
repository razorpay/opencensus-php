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

    // ========== Counters ==============

    // =========== Histograms ===========
    const RECON_PAYMENT_CREATE_TO_RECONCILED_TIME_MINUTES = 'recon_payment_create_to_reconciled_time_minutes.histogram';
    const RECON_REFUND_CREATE_TO_RECONCILED_TIME_MINUTES  = 'recon_refund_create_to_reconciled_time_minutes.histogram';
    const RECON_REFUND_CREATED_TO_PROCESSED_TIME_MINUTES  = 'recon_refund_created_to_processed_time_minutes.histogram';
    const RECON_MIS_FILE_PARSING_TIME_SECONDS             = 'recon_mis_file_parsing_time_seconds.histogram';
    const RECON_MIS_FILE_PROCESSING_TIME_SECONDS          = 'recon_mis_file_processing_time_seconds.histogram';


    // ======================= END METRICS =======================

    // ======================= DIMENSIONS =======================

    const SOURCE            = 'source';
    const GATEWAY           = 'gateway';
    const METHOD            = 'method';
    const GATEWAY_ACQUIRER  = 'gateway_acquirer';

    // ======================= END DIMENSIONS =======================

    /**
     * Gets dimensions for payment metrics depending
     *
     * @param PaymentEntity $payment
     * @param $source
     * @return array
     */
    public static function getPaymentMetricDimensions(PaymentEntity $payment, string $source = null): array
    {
        $allDimensions = [
                self::SOURCE    =>  $source,
                self::GATEWAY   =>  $payment->getGateway(),
                self::METHOD    =>  $payment->getMethod(),
            ];

        if ($payment->getTerminalId() !== null)
        {
            if (empty($payment->terminal->getGatewayAcquirer()) === false)
            {
                $allDimensions[self::GATEWAY_ACQUIRER] = $payment->terminal->getGatewayAcquirer();
            }
            else
            {
                $allDimensions[self::GATEWAY_ACQUIRER] = $allDimensions[self::GATEWAY];
            }
        }

        return $allDimensions;
    }

    public static function getRefundMetricDimensions(RefundEntity $refund, string $source = null): array
    {
        $allDimensions = [
                self::SOURCE    =>  $source,
                self::GATEWAY   =>  $refund->getGateway(),
                self::METHOD    =>  $refund->payment->getMethod(),
            ];

        if ($refund->payment->getTerminalId() !== null)
        {
            if (empty($refund->payment->terminal->getGatewayAcquirer()) === false)
            {
                $allDimensions[self::GATEWAY_ACQUIRER] = $refund->payment->terminal->getGatewayAcquirer();
            }
            else
            {
                $allDimensions[self::GATEWAY_ACQUIRER] = $allDimensions[self::GATEWAY];
            }
        }

        return $allDimensions;
    }

    public static function getFileProcessingMetricDimension(string $gateway, string $source = null)
    {
        $allDimensions = [
            self::SOURCE    =>  $source,
            self::GATEWAY   =>  $gateway,
        ];

        return $allDimensions;
    }
}
