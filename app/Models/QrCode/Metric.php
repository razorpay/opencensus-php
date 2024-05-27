<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity;
use RZP\Models\QrCode\NonVirtualAccountQrCode\RequestSource;

class Metric extends Base\Core
{
    const QR_CODE_CREATE_SUCCESS                    = 'qr_code_create_success';
    const QR_CODE_CREATE_FAILED                     = 'qr_code_create_failed';
    const QR_CODE_CREATE_LATENCY                    = 'qr_code_create_latency';
    const QR_CODE_REMINDER_REGISTRATION_LATENCY     = 'qr_code_reminder_registration_latency';
    const QR_CODE_REMINDER_RESPONSE_STATUS_CODE     = 'qr_code_reminder_response_status_code';
    const QR_PAYMENT_CREATION_SOURCE                = 'qr_payment_creation_source';
    const QR_CODE_CREATE_LATENCY_WIHTOUT_GW         = 'qr_code_create_latency_without_gw';
    const QR_CODE_CLOSE_SUCCESS                     = 'qr_code_close_success';
    const QR_CODE_CLOSE_FAILED                      = 'qr_code_close_failed';
    const QR_STATUS_CHECK_REMINDER_CALLBACK_LATENCY = 'qr_status_check_reminder_callback_latency';
    const QR_STATUS_CHECK_GATEWAY_LATENCY           = 'qr_status_check_gateway_latency';
    const QR_STATUS_CHECK_PAYMENT_CREATION_FAILURE  = 'qr_status_check_payment_creation_failure';
    const QR_STATUS_CHECK_SQS_MESSAGE_DISPATCH_FAILED = 'qr_status_check_sqs_message_dispatch_failed';

    const LABEL_MERCHANT_ID   = 'merchant_id';
    const LABEL_CLOSE_REASON  = 'close_reason';
    const LABEL_ERROR_MESSAGE = 'error_message';
    const LABEL_PROVIDER      = 'provider';
    const LABEL_USAGE_TYPE    = 'usage_type';
    const LABEL_GATEWAY       = 'gateway';

    const LABEL_REQUEST_SOURCE = 'request_source';

    protected function getDefaultDimensions($requestSource): array
    {
        if ($requestSource === RequestSource::CHECKOUT || $requestSource === RequestSource::PAYMENT_LINKS) {
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

    public function pushCreateMetrics($input, $errorMessage)
    {
        $requestSource = $input[Entity::REQUEST_SOURCE] ?? null;

        $dimensions = $this->getDefaultDimensions($requestSource);

        $customDimensions = [
            Metric::LABEL_PROVIDER      => $input[Entity::REQ_PROVIDER],
            Metric::LABEL_USAGE_TYPE    => $input[Entity::REQ_USAGE_TYPE],
            Metric::LABEL_ERROR_MESSAGE => ($errorMessage === null) ? $errorMessage : substr($errorMessage, 0, 100),
            self::LABEL_REQUEST_SOURCE  => $requestSource,
            self::LABEL_GATEWAY         => $input[Entity::GATEWAY] ?? '',
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

    public function pushCreateLatencyMetrics($input, $startTimeMs, $gatewaylatency)
    {
        $processingTimeMs = (microtime(true) * 1000) - $startTimeMs;

        $requestSource = $input[Entity::REQUEST_SOURCE] ?? null;

        $dimensions = [
            self::LABEL_REQUEST_SOURCE  => $requestSource,
            Metric::LABEL_PROVIDER      => $input[Entity::REQ_PROVIDER],
            self::LABEL_GATEWAY         => $input[Entity::GATEWAY],
        ];

        $this->trace->histogram(self::QR_CODE_CREATE_LATENCY, $processingTimeMs, $dimensions);

        $processingTimeWithoutGatewayMs = $processingTimeMs - $gatewaylatency;

        $this->trace->histogram(self::QR_CODE_CREATE_LATENCY_WIHTOUT_GW, $processingTimeWithoutGatewayMs, $dimensions);
    }

    public function pushCloseMetrics($closeReason, $errorMessage, $requestSource)
    {
        $dimensions = $this->getDefaultDimensions($requestSource);

        $customDimensions = [
            Metric::LABEL_CLOSE_REASON  => $closeReason,
            Metric::LABEL_ERROR_MESSAGE => $errorMessage,
            self::LABEL_REQUEST_SOURCE  => $requestSource,
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
