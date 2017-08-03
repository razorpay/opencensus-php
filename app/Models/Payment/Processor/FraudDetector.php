<?php

namespace RZP\Models\Payment\Processor;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Risk;
use RZP\Trace\Trace;
use RZP\Exception;
use RZP\Models\Payment\Analytics\Metadata;

trait FraudDetector
{
    protected function validateFraudDetection($payment)
    {
        $riskScore = $this->getRiskScore($payment);

        if (($payment->shouldFailOnRiskFailure() === true) and
            ($riskScore > 5))
        {
            $data = [
                'payment_id' => $payment->getPublicId(),
                'risk_score' => $riskScore,
            ];

            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD;

            $e = new Exception\BadRequestException($errorCode, null, $data);

            $this->updatePaymentAuthFailed($e);

            (new Risk\Core)->logPaymentOnMaxmindFailure($payment, $riskScore);

            throw $e;
        }
    }

    public function getRiskScore($payment)
    {
        $riskFields = $this->getRiskDetectionField($payment);

        if ((isset($riskFields) === true) and
            (isset($riskFields['riskScore']) === true))
        {
            $this->setRiskMetadata($payment, $riskFields);

            return (float) $riskFields['riskScore'];
        }

        return 0;
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
