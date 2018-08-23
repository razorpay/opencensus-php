<?php

namespace RZP\Models\Payment\Processor;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Risk;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Exception;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Analytics\Metadata;

trait FraudDetector
{
    protected function validateFraudDetection(Payment\Entity $payment, Merchant\Entity $merchant)
    {
        $riskScore = $this->getRiskScore($payment);

        if ($riskScore > $merchant->getRiskThreshold())
        {
            $data = [
                'payment_id' => $payment->getPublicId(),
                'risk_score' => $riskScore,
            ];

            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD;

            $e = new Exception\BadRequestException($errorCode, null, $data);

            $this->updatePaymentAuthFailed($e);

            $riskData = [
                Risk\Entity::RISK_SCORE => $riskScore,
                Risk\Entity::REASON     => Risk\RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_MAXMIND,
                Risk\Entity::FRAUD_TYPE => Risk\Type::SUSPECTED,
            ];

            (new Risk\Core)->logPaymentForSource($payment, Risk\Source::MAXMIND, $riskData);

            throw $e;
        }
    }

    public function getRiskScore(Payment\Entity $payment)
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

    protected function getRiskDetectionField(Payment\Entity $payment)
    {
        $response = null;

        try
        {
            $response = $this->app['maxmind']->query($payment);
        }
        catch (\MaxMind\Exception\IpAddressNotFoundException $e)
        {
            $this->trace->traceException($e, Trace::INFO);
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
        $paymentAnalytics = $payment->getMetaData("payment_analytics");

        if (is_null($paymentAnalytics) === false)
        {
            $paymentAnalytics->setRiskScore($riskFields['riskScore']);
            $paymentAnalytics->setRiskEngine(Metadata::MAXMIND);
        }
    }

    protected function validateFraudDetectionV2(Payment\Entity $payment)
    {
        if (($this->app['config']->get('app.env') === Environment::PRODUCTION) and
            ($this->mode === Mode::TEST))
        {
            $this->trace->info(
                TraceCode::FRAUD_DETECTION_SKIPPED,
                [
                    'payment_id'  => $payment->getPublicId(),
                    'environment' => Environment::PRODUCTION,
                    'mode'        => Mode::TEST,
                ]
            );

            return;
        }

        $riskData = $this->app['shield.service']->getRiskAssessment($payment);

        if (empty($riskData) === false)
        {
            $payment->setMetadataKey(
                'risk_entity',
                [
                    "source"    => Risk\Source::SHIELD,
                    "risk_data" => $riskData,
                ]
            );

            if ($riskData[Risk\Entity::FRAUD_TYPE] === Risk\Type::CONFIRMED)
            {
                $data = [
                    'payment_id' => $payment->getPublicId(),
                    'risk_data'  => $riskData,
                ];

                $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD;

                $e = new Exception\BadRequestException($errorCode, null, $data);

                $this->updatePaymentAuthFailed($e);

                throw $e;
            }
        }
    }
}
