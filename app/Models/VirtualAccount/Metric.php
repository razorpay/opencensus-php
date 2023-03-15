<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Models\Base;

class Metric extends Base\Core
{
    const VIRTUAL_ACCOUNT_CREATE_SUCCESS           = 'virtual_account_create_success';
    const VIRTUAL_ACCOUNT_CREATE_FAILED            = 'virtual_account_create_failed';
    const VIRTUAL_ACCOUNT_CLOSE_SUCCESS            = 'virtual_account_close_success';
    const VIRTUAL_ACCOUNT_CLOSE_FAILED             = 'virtual_account_close_failed';
    const VIRTUAL_ACCOUNT_PAYMENT                  = 'virtual_account_payment';
    const VIRTUAL_ACCOUNT_REFUND                   = 'virtual_account_refund';
    const VIRTUAL_ACCOUNT_PAYMENT_SQS_PUSH         = 'virtual_account_payment_sqs_push';
    const VIRTUAL_ACCOUNT_PAYMENT_PROCESSING_TIME  = 'virtual_account_payment_processing_time';
    const SMART_COLLECT_TERMINAL_CACHING_HIT       = 'smart_collect_terminal_caching_hit';
    const SMART_COLLECT_TERMINAL_CACHING_MISS      = 'smart_collect_terminal_caching_miss';
    const SMART_COLLECT_TERMINAL_CACHING_EXCEPTION = 'smart_collect_terminal_caching_exception';

    const LABEL_TRACE_CODE                  = 'code';
    const LABEL_HAS_BANK_ACCOUNT            = 'has_bank_account';
    const LABEL_HAS_QR_CODE                 = 'has_qr_code';
    const LABEL_HAS_VPA                     = 'has_vpa';
    const LABEL_MERCHANT_ID                 = 'merchant_id';

    const LABEL_GATEWAY_REQUEST_CREATED_AT      = 'callback_request_created_at';
    const LABEL_GATEWAY_REQUEST_COMPLETED_AT    = 'callback_request_completed_at';
    const LABEL_GATEWAY                         = 'gateway';
    const LABEL_ERROR_MESSAGE                   = 'error_message';

    const LABEL_MODE                            = 'mode';
    const LABEL_ROUTE_NAME                      = 'route_name';

    protected function getDefaultDimensions(array $input): array
    {
        $receivers = isset($input[Entity::RECEIVERS]) === true ? $input[Entity::RECEIVERS] : null;

        if ((is_array($receivers) === true) and
            (isset($receivers[Entity::TYPES]) === true) and
            (is_array($receivers[Entity::TYPES]) === true))
        {
            $types = $receivers[Entity::TYPES];
        }
        else
        {
            $types = [];
        }

        $dimensions = [
            Metric::LABEL_HAS_BANK_ACCOUNT       => in_array(Receiver::BANK_ACCOUNT, $types),
            Metric::LABEL_HAS_QR_CODE            => in_array(Receiver::QR_CODE, $types),
            Metric::LABEL_HAS_VPA                => in_array(Receiver::VPA, $types),
            Metric::LABEL_MERCHANT_ID            => $this->merchant ? $this->merchant->getId() : null,
        ];

        return $dimensions;
    }

    public function pushCreateSuccessMetrics(array $input)
    {
        $dimensions = $this->getDefaultDimensions($input);

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_CREATE_SUCCESS,
            $dimensions
        );
    }

    public function pushCreateFailedMetrics(array $input, \Throwable $e)
    {
        $dimensions = $this->getDefaultDimensions($input);

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_CREATE_FAILED,
            array_merge([
                    Metric::LABEL_TRACE_CODE  => $e->getCode(),
                ],
                $dimensions
            )
        );
    }

    public function pushCloseSuccessMetrics(array $input)
    {
        $dimensions = $this->getDefaultDimensions($input);

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_CLOSE_SUCCESS,
            $dimensions
        );
    }

    public function pushCloseFailedMetrics(array $input, \Throwable $e)
    {
        $dimensions = $this->getDefaultDimensions($input);

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_CLOSE_FAILED,
            array_merge([
                Metric::LABEL_TRACE_CODE => $e->getCode(),
                ],
                $dimensions
            )
        );
    }

    public function pushPaymentMetrics(string $method, bool $isExpected = null, bool $success = false,
                                       string $gateway = null, string $error = null, array $extraDimensions = [])
    {
        $dimensions = [
            'method'            => $method,
            'expected'          => $isExpected,
            'successful'        => $success,
            'gateway'           => $gateway,
            'error'             => $error,
        ];

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_PAYMENT,
            $dimensions
        );
    }

    public function pushRefundMetrics(array $input)
    {
        $dimensions = $this->getDefaultDimensions($input);

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_REFUND,
            $dimensions
        );
    }

    public function pushSqsPushMetrics(string $method, string $gateway, bool $isPushedToQueue)
    {
        $dimensions = [
            'method'          => $method,
            'isPushedToQueue' => $isPushedToQueue,
            'gateway'         => $gateway,
        ];

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_PAYMENT_SQS_PUSH,
            $dimensions
        );
    }

    public function pushQueueTimeMetrics(int $created_at, int $completed_at, string $gateway, string $errorMessage = null)
    {
        $dimensions = [
            Metric::LABEL_GATEWAY_REQUEST_CREATED_AT      => $created_at,
            Metric::LABEL_GATEWAY_REQUEST_COMPLETED_AT    => $completed_at,
            Metric::LABEL_GATEWAY                         => $gateway,
            Metric::LABEL_ERROR_MESSAGE                   => $errorMessage,
        ];

        $this->trace->histogram(
            Metric::VIRTUAL_ACCOUNT_PAYMENT_PROCESSING_TIME,
            $completed_at - $created_at,
            $dimensions
        );
    }
}
