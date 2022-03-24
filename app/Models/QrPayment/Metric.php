<?php

namespace RZP\Models\QrPayment;

use App;
use RZP\Models\Base;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity;

class Metric extends Base\Core
{
    const QR_CODE_V2_PAYMENT_PROCESS = 'qr_code_v2_payment_process';

    const LABEL_MERCHANT_ID   = 'merchant_id';
    const LABEL_METHOD        = 'method';
    const LABEL_EXPECTED      = 'expected';
    const LABEL_GATEWAY       = 'gateway';
    const LABEL_ERROR_MESSAGE = 'error_message';
    const LABEL_SUCCESSFUL    = 'successful';

    protected function getDefaultDimensions(): array
    {
        $dimensions = [
            Metric::LABEL_MERCHANT_ID => $this->merchant ? $this->merchant->getId() : null,
        ];

        return $dimensions;
    }

    public function pushQrV2PaymentsMetrics($isExpected, $valid, $gateway, $method, $errorMessage)
    {
        $dimensions = $this->getDefaultDimensions();

        $customDimensions = [
            Metric::LABEL_EXPECTED      => $isExpected,
            Metric::LABEL_GATEWAY       => $gateway,
            Metric::LABEL_METHOD        => $method,
            Metric::LABEL_SUCCESSFUL    => $valid,
            Metric::LABEL_ERROR_MESSAGE => $errorMessage,
        ];

        $this->trace->count(
            Metric::QR_CODE_V2_PAYMENT_PROCESS,
            array_merge($customDimensions, $dimensions)
        );
    }
}
