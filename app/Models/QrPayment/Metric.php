<?php

namespace RZP\Models\QrPayment;

use App;
use RZP\Models\Base;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity;
use RZP\Models\QrCode\NonVirtualAccountQrCode\RequestSource;

class Metric extends Base\Core
{
    const QR_CODE_V2_PAYMENT_PROCESS = 'qr_code_v2_payment_process';
    const QR_CODE_V2_PAYMENT_ES_SYNC = 'qr_code_v2_payment_es_sync';
    const QR_CODE_V2_PAYMENT_LATENCY = 'qr_code_v2_payment_latency';

    const LABEL_MERCHANT_ID          = 'merchant_id';
    const LABEL_METHOD               = 'method';
    const LABEL_EXPECTED             = 'expected';
    const LABEL_GATEWAY              = 'gateway';
    const LABEL_ERROR_MESSAGE        = 'error_message';
    const LABEL_SUCCESSFUL           = 'successful';
    const LABEL_REQUEST_SOURCE       = 'request_source';
    const LABEL_ES_SYNC_ERROR_MESSAGE= 'es_sync_error_message';
    const LABEL_SHARED_TERMINAL      = 'shared_terminal';

    protected function getDefaultDimensions($requestSource): array
    {
        if ($requestSource === RequestSource::CHECKOUT) {
            // Not adding merchant_id in checkout qr codes as cardinality
            // would be very high
            return [];
        }

        // ToDo: Remove logging of MerchantId to prometheus as we should avoid
        // pushing high cardinality data to it.
        $dimensions = [
            Metric::LABEL_MERCHANT_ID => $this->merchant ? $this->merchant->getId() : null,
        ];

        return $dimensions;
    }

    public function pushQrV2PaymentsMetrics($isExpected, $valid, $gateway, $method, $errorMessage, $requestSource,
                                            $isSharedTerminalPayment, $processingTime)
    {
        $dimensions = $this->getDefaultDimensions($requestSource);

        $customDimensions = [
            Metric::LABEL_EXPECTED      => $isExpected,
            Metric::LABEL_GATEWAY       => $gateway,
            Metric::LABEL_METHOD        => $method,
            Metric::LABEL_SUCCESSFUL    => $valid,
//            Metric::LABEL_ERROR_MESSAGE => $errorMessage,
            self::LABEL_REQUEST_SOURCE  => $requestSource,
            self::LABEL_SHARED_TERMINAL => $isSharedTerminalPayment,
        ];

        $metricDimensions = array_merge($customDimensions, $dimensions);

        $this->trace->count(Metric::QR_CODE_V2_PAYMENT_PROCESS, $metricDimensions);

        $this->trace->histogram(self::QR_CODE_V2_PAYMENT_LATENCY, (int)$processingTime, $metricDimensions);
    }

    public function pushQrV2PaymentsESSyncMetrics($errorMessage)
    {
        $dimensions = [];

        $customDimensions = [
            Metric::LABEL_ES_SYNC_ERROR_MESSAGE => $errorMessage
        ];

        $this->trace->count(
            Metric::QR_CODE_V2_PAYMENT_ES_SYNC,
            array_merge($customDimensions, $dimensions)
        );
    }

}
