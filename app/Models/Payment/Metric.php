<?php

namespace RZP\Models\Payment;

use App;
use RZP\Constants\Metric as MetricName;
use RZP\Models\Base;
use RZP\Models\Payment;

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
    const LABEL_PAYMENT_ERROR_CODE              = 'error_code';
    const LABEL_PAYMENT_IS_CREATED              = 'is_created';
    const LABEL_TRACE_CODE                      = 'code';
    const LABEL_TRACE_FIELD                     = 'field';
    const LABEL_TRACE_SOURCE                    = 'source';

    // Metric Names
    const PAYMENT_CREATED                       = 'payment_created';
    const PAYMENT_AUTHORIZED                    = 'payment_authorized';
    const PAYMENT_CAPTURED                      = 'payment_captured';
    const PAYMENT_FAILED                        = 'payment_failed';
    const PAYMENT_PROCESS_FAILED                = 'payment_process_failed';

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

    public function pushAuthMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentAuthDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $authTime = ($payment->getAuthorizeTimestamp() - $payment->getCreatedAt());

        $this->trace->histogram(self::PAYMENT_AUTHORIZED, $authTime, $dimensions);
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
            self::LABEL_PAYMENT_AUTO_CAPTURED => $payment->getAutoCaptured(),
        ];

        return $dimensions;
    }

    protected function getFormattedStatus(Entity $payment)
    {
        return ($payment->getStatus() . '_' . $payment->getInternalErrorCode());
    }
}
