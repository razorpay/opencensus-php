<?php

namespace RZP\Models\Payment\Processor;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Trace\Trace;
use RZP\Exception;

trait FraudDetector
{
    protected function validateFraudDetection($payment)
    {
        $riskFields = $this->getRiskDetectionField($payment);

        if ((isset($riskFields) === true) and
            (isset($riskFields['riskScore']) === true) and
            ((float) $riskFields['riskScore'] > 20))
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD);
        }
    }

    protected function getRiskDetectionField($payment)
    {
        $response = null;

        try
        {
            $response = $this->app['maxmind']->query($payment);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::RECOVERABLE_EXCEPTION);
        }

        return $response;
    }
}