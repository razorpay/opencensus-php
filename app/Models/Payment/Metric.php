<?php

namespace RZP\Models\Payment;

use App;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;

class Metric extends Base\Core
{
    // Labels for Payment Metrics
    const LABEL_PAYMENT_GATEWAY                 = 'gateway';
    const LABEL_PAYMENT_METHOD                  = 'method';
    const LABEL_PAYMENT_CURRENCY                = 'currency';
    const LABEL_PAYMENT_INTERNATIONAL           = 'international';
    const LABEL_PAYMENT_ISSUER                  = 'issuer';
    const LABEL_PAYMENT_TRANSACTION_TYPE        = 'transaction_type';
    const LABEL_PAYMENT_STATUS                  = 'status';
    const LABEL_CARD_TYPE                       = 'card_type';
    const LABEL_CARD_NETWORK                    = 'card_network';
    const LABEL_PAYMENT_LATE_AUTHORIZED         = 'late_authorized';
    const LABEL_PAYMENT_AUTO_CAPTURED           = 'auto_captured';
    const LABEL_PAYMENT_GATEWAY_CAPTURED        = 'gateway_captured';
    const LABEL_PAYMENT_ERROR_CODE              = 'error_code';
    const LABEL_PAYMENT_IS_CREATED              = 'is_created';
    const LABEL_TRACE_CODE                      = 'code';
    const LABEL_TRACE_FIELD                     = 'field';
    const LABEL_TRACE_SOURCE                    = 'source';
    const LABEL_TRACE_EXCEPTION_CLASS           = 'exception_class';

    // Metric Names
    const PAYMENT_CREATED                       = 'payment_created';
    const PAYMENT_AUTHORIZED                    = 'payment_authorized_v1';
    const PAYMENT_CAPTURED                      = 'payment_captured_v1';
    const PAYMENT_CREATE_REQUEST_TIME           = 'payment_create_request_time_v1.';
    const PAYMENT_FAILED                        = 'payment_failed';
    const PAYMENT_PROCESS_FAILED                = 'payment_process_failed';
    const PAYMENT_CAPTURE_FAILED                = 'payment_capture_failed';
    const PAYMENT_REQUEST_ROUTE                 = 'payment_request_route';

    public function pushCreateMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentCreatedDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count(self::PAYMENT_CREATED, $dimensions);
    }

    public function pushFailedMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentFailedDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count(self::PAYMENT_FAILED, $dimensions);
    }

    public function pushExceptionMetrics(\Throwable $e, string $metricName, array $extraDimensions = [])
    {
        $dimensions = $this->getDefaultExceptionDimensions($e);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count($metricName, $dimensions);
    }

    public function pushAuthMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentAuthDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $authTime = ($payment->getAuthorizeTimestamp() - $payment->getCreatedAt());

        $this->trace->histogram(self::PAYMENT_AUTHORIZED, $authTime, $dimensions);
    }

    public function pushCreateRequestTimeMetrics(Entity $payment, string $route, int $requestTime)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = [
            self::PAYMENT_REQUEST_ROUTE => $route,
        ];

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->histogram(self::PAYMENT_CREATE_REQUEST_TIME, $requestTime, $dimensions);
    }

    public function pushCapturedMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentCapturedDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $captureTime = ($payment->getCapturedAt() - $payment->getCreatedAt());

        $this->trace->histogram(self::PAYMENT_CAPTURED, $captureTime, $dimensions);
    }

    protected function getDefaultDimentions(Entity $payment)
    {
        $dimensions = [
            self::LABEL_PAYMENT_GATEWAY          => $payment->getGateway(),
            self::LABEL_PAYMENT_METHOD           => $payment->getMethod(),
            self::LABEL_PAYMENT_ISSUER           => $payment->getIssuer(),
            self::LABEL_PAYMENT_CURRENCY         => $payment->getCurrency(),
            self::LABEL_PAYMENT_INTERNATIONAL    => $payment->isInternational(),
            self::LABEL_PAYMENT_TRANSACTION_TYPE => $payment->getTransactionType(),
        ];

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;

            $cardType = $card->getType();

            $network = $card->getNetwork();

            $iin = $card->getIin();
        }

        $dimensions += [
            self::LABEL_CARD_NETWORK => $network  ?? null,
            self::LABEL_CARD_TYPE    => $cardType ?? null,
        ];

        return $dimensions;
    }

    protected function getDefaultExceptionDimensions(\Throwable $e): array
    {
        $errorAttributes = [];

        if ($e instanceof Exception\BaseException)
        {
            if (($e->getError() !== null) and ($e->getError() instanceof Error))
            {
                $errorAttributes = $e->getError()->getAttributes();
            }
        }
        else
        {
            $errorAttributes = [
                Metric::LABEL_TRACE_CODE         => $e->getCode(),
            ];
        }

        $dimensions = [
            Metric::LABEL_TRACE_CODE                => array_get($errorAttributes, Error::INTERNAL_ERROR_CODE),
            Metric::LABEL_TRACE_FIELD               => array_get($errorAttributes, Error::FIELD),
            Metric::LABEL_TRACE_SOURCE              => array_get($errorAttributes, Error::ERROR_CLASS),
            Metric::LABEL_TRACE_EXCEPTION_CLASS     => get_class($e),
        ];

        return $dimensions;
    }

    protected function getPaymentCreatedDimensions(Entity $payment)
    {
        $dimensions = [
            self::LABEL_PAYMENT_STATUS => $this->getFormattedStatus($payment),
        ];

        return $dimensions;
    }

    protected function getPaymentFailedDimensions(Entity $payment)
    {
        $dimensions = [
            self::LABEL_PAYMENT_ERROR_CODE => $payment->getInternalErrorCode(),
        ];

        return $dimensions;
    }

    protected function getPaymentAuthDimensions(Entity $payment)
    {
        $dimensions = [
            self::LABEL_PAYMENT_LATE_AUTHORIZED => $payment->isLateAuthorized(),
        ];

        return $dimensions;
    }

    protected function getPaymentCapturedDimensions(Entity $payment)
    {
        $dimensions = [
            self::LABEL_PAYMENT_AUTO_CAPTURED       => $payment->getAutoCaptured(),
            self::LABEL_PAYMENT_GATEWAY_CAPTURED    => $payment->getGatewayCaptured(),
        ];

        return $dimensions;
    }

    protected function getFormattedStatus(Entity $payment)
    {
        return ($payment->getStatus() . '_' . $payment->getInternalErrorCode());
    }
}
