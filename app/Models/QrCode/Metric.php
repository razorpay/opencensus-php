<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity;

class Metric extends Base\Core
{
    const QR_CODE_CREATE_SUCCESS = 'qr_code_create_success';
    const QR_CODE_CREATE_FAILED  = 'qr_code_create_failed';
    const QR_CODE_CLOSE_SUCCESS  = 'qr_code_close_success';
    const QR_CODE_CLOSE_FAILED   = 'qr_code_close_failed';

    const LABEL_MERCHANT_ID   = 'merchant_id';
    const LABEL_CLOSE_REASON  = 'close_reason';
    const LABEL_ERROR_MESSAGE = 'error_message';
    const LABEL_PROVIDER      = 'provider';
    const LABEL_USAGE_TYPE    = 'usage_type';

    protected function getDefaultDimensions(): array
    {
        $dimensions = [
            Metric::LABEL_MERCHANT_ID => $this->merchant ? $this->merchant->getId() : null,
        ];

        return $dimensions;
    }

    public function pushCreateMetrics($input, $errorMessage)
    {
        $dimensions = $this->getDefaultDimensions();

        $customDimensions = [
            Metric::LABEL_PROVIDER      => $input[Entity::REQ_PROVIDER],
            Metric::LABEL_USAGE_TYPE    => $input[Entity::REQ_USAGE_TYPE],
            Metric::LABEL_ERROR_MESSAGE => $errorMessage,
        ];

        $metric = Metric::QR_CODE_CREATE_SUCCESS;

        if ($errorMessage != null)
        {
            $metric = Metric::QR_CODE_CREATE_FAILED;
        }

        $this->trace->count(
            $metric,
            array_merge($customDimensions, $dimensions)
        );
    }

    public function pushCloseMetrics($closeReason, $errorMessage)
    {
        $dimensions = $this->getDefaultDimensions();

        $customDimensions = [
            Metric::LABEL_CLOSE_REASON  => $closeReason,
            Metric::LABEL_ERROR_MESSAGE => $errorMessage
        ];

        $metric = Metric::QR_CODE_CLOSE_SUCCESS;

        if ($errorMessage != null)
        {
            $metric = Metric::QR_CODE_CLOSE_FAILED;
        }

        $this->trace->count(
            $metric,
            array_merge($customDimensions, $dimensions)
        );
    }
}
