<?php

namespace RZP\Models\Payment\Processor;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Trace\Trace;
use RZP\Exception;
use RZP\Models\Payment\Analytics\Metadata;

trait FraudDetector
{
    protected function validateFraudDetection($payment)
    {
        $riskFields = $this->getRiskDetectionField($payment);

        if ((isset($riskFields) === true) and
            (isset($riskFields['riskScore']) === true))
        {
            $this->setRiskMetadata($payment, $riskFields);

            if ((float) $riskFields['riskScore'] > 5)
            {
                $e = new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD);

                $this->updatePaymentAuthFailedAndThrowException($e);
            }
        }
    }

    protected function getRiskDetectionField($payment)
    {
        $response = null;

        try
        {
            $response = $this->app['maxmind']->query($payment);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::RECOVERABLE_EXCEPTION);
        }

        return $response;
    }

    /**
     * Sets risk related metadata in payment metadata
     *
     * @param $payment    Payment\Entity
     * @param $riskFields array
     * @return void
     */
    protected function setRiskMetadata(Payment\Entity $payment, array $riskFields)
    {
        $data = [
            Metadata::RISK_SCORE  => $riskFields['riskScore'],
            Metadata::RISK_ENGINE => Metadata::MAXMIND,
        ];

        foreach ($data as $key => $value)
        {
            $payment->setMetadataKey($key, $value);
        }
    }
}
