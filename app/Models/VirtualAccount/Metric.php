<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Models\Base;

class Metric extends Base\Core
{
    const VIRTUAL_ACCOUNT_CREATE_SUCCESS        = 'virtual_account_create_success';
    const VIRTUAL_ACCOUNT_CREATE_FAILED         = 'virtual_account_create_failed';
    const VIRTUAL_ACCOUNT_CLOSE_SUCCESS         = 'virtual_account_close_success';
    const VIRTUAL_ACCOUNT_CLOSE_FAILED          = 'virtual_account_close_failed';
    const VIRTUAL_ACCOUNT_PAYMENT               = 'virtual_account_payment';
    const VIRTUAL_ACCOUNT_REFUND                = 'virtual_account_refund';
    const VIRTUAL_ACCOUNT_PAYMENT_SQS_PUSH      = 'virtual_account_payment_sqs_push';

    const LABEL_TRACE_CODE                  = 'code';
    const LABEL_HAS_BANK_ACCOUNT            = 'has_bank_account';
    const LABEL_HAS_QR_CODE                 = 'has_qr_code';
    const LABEL_HAS_VPA                     = 'has_vpa';

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

    public function pushPaymentMetrics(string $method, bool $isExpected = null, bool $success = false, string $gateway = null)
    {
        $dimensions = [
            'method'            => $method,
            'expected'          => $isExpected,
            'successful'        => $success,
            'gateway'           => $gateway,
        ];

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
}
