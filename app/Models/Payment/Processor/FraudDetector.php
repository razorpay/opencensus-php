<?php

namespace RZP\Models\Payment\Processor;

use RZP\Constants\Shield;
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
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\Payment\Config\Type as PaymentConfigType;

trait FraudDetector
{
    protected function validateFraudDetection(Payment\Entity $payment, Merchant\Entity $merchant)
    {
        $riskScore = $this->getRiskScore($payment);

        $variant = $this->getRiskEngineVersionVariant($merchant);

        $this->trace->info(TraceCode::RAZORX_VARIANT_3DS, [
            'payment_id'     => $payment->getId(),
            'razorx_variant' => $variant,
        ]);

        $riskEngine = ($variant !== 'v2') ? Metadata::MAXMIND : Metadata::MAXMIND_V2;

        $riskFields = [
            'riskScore'   => $riskScore,
            'riskEngine'  => $riskEngine,
        ];

        $this->setRiskMetadata($payment, $riskFields);

        if (($variant !== 'v2') and
            ($riskScore > $merchant->getRiskThreshold()))
        {
            $data = [
                'payment_id' => $payment->getPublicId(),
                'method'     => $payment->getMethod(),
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
        $paymentAnalytics = $payment->getMetaData('payment_analytics');

        if (is_null($paymentAnalytics) === false)
        {
            $paymentAnalytics->setRiskScore($riskFields['riskScore']);
            $paymentAnalytics->setRiskEngine($riskFields['riskEngine']);
        }
    }

    protected function isEligibleForStoringPackageName($riskData, $shieldPayload): bool
    {
        $shieldPayloadInput = $shieldPayload[Shield::INPUT] ?? [];

        // note: platform might change from mobile_sdk to android_mobile_sdk in the future
        return (
            (isset($riskData[Risk\Entity::FRAUD_TYPE]) === false ||
             $riskData[Risk\Entity::FRAUD_TYPE] !== Risk\Type::CONFIRMED)
            && isset($shieldPayloadInput[Shield::PACKAGE_NAME]) === true
            && isset($shieldPayloadInput[Shield::PLATFORM]) === true
            && $shieldPayloadInput[Shield::PLATFORM] === Shield::MOBILE_SDK
            && isset($shieldPayloadInput[Shield::OS]) === true
            && $shieldPayloadInput[Shield::OS] === Shield::ANDROID
        );
    }

    protected function savePackageNameIfApplicable(& $riskData, $merchant)
    {
        try {
            $shieldPayload = $riskData[Shield::EVALUATION_PAYLOAD] ?? [];

            unset($riskData[Shield::EVALUATION_PAYLOAD]);

            $variant = $this->app->razorx->getTreatment($merchant->getId(), Merchant\RazorxTreatment::SAVE_TXN_APP_URLS, $this->mode);

            if ($variant !== 'on' || $this->isEligibleForStoringPackageName($riskData, $shieldPayload) === false)
            {
                return;
            }

            $currentUrl = sprintf('%s%s', BusinessDetail\Constants::PLAYSTORE_URL_PREFIX, $shieldPayload[Shield::INPUT][Shield::PACKAGE_NAME]);

            (new BusinessDetail\Service())->saveBusinessDetailsForMerchant($merchant->getId(), [
                BusinessDetail\Constants::TXN_URL => $currentUrl,
            ]);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::TXN_APP_URL_NOT_SAVED, [
                'merchant_id' => $merchant->getId(),
            ]);
        }
    }

    protected function validateFraudDetectionV2(Payment\Entity $payment, Merchant\Entity $merchant, $input)
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

        $variant = $this->getRiskEngineVersionVariant($merchant);

        $this->trace->info(TraceCode::RAZORX_VARIANT_3DS, [
            'payment_id'     => $payment->getId(),
            'razorx_variant' => $variant,
        ]);

        $riskSource = Risk\Source::SHIELD;

        $riskEngine = ($variant !== 'v2') ? Metadata::SHIELD : Metadata::SHIELD_V2;

        $riskData = $this->app['shield.service']->getRiskAssessment($payment, $input);

        $this->savePackageNameIfApplicable($riskData, $merchant);

        $triggeredRules = $riskData[Shield::TRIGGERED_RULES] ?? [];

        unset($riskData[Shield::TRIGGERED_RULES]);

        if (is_null($riskData['risk_score']) === false)
        {
            $riskFields = [
                'riskScore'   => $riskData['risk_score'],
                'riskEngine'  => $riskEngine,
            ];

            $this->setRiskMetadata($payment, $riskFields);

            if (($variant !== 'v2') and
                (($riskData['risk_score'] > $this->merchant->getRiskThreshold()) and
                 (($this->payment->card->isInternational() === true) and ($this->payment->card->isAmex() === false))))
            {
                $riskData[Risk\Entity::FRAUD_TYPE] = Risk\Type::CONFIRMED;
                $riskData[Risk\Entity::REASON]     = Risk\RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_SHEILD;
                $riskSource = Risk\Source::SHIELD;
            }
        }

        if (empty($riskData[Risk\Entity::FRAUD_TYPE]) === false)
        {
            $payment->setMetadataKey(
                'risk_entity',
                [
                    'source'    => $riskSource,
                    'risk_data' => $riskData,
                ]
            );

            (new Payment\Fraud\Notify())->notifyOpsIfNeeded($merchant, $triggeredRules);

            if ($riskData[Risk\Entity::FRAUD_TYPE] === Risk\Type::CONFIRMED)
            {
                $data = [
                    'payment_id' => $payment->getPublicId(),
                    'method'     => $payment->getMethod(),
                    'risk_data'  => $riskData,
                ];

                $errorCode = $this->getErrorCodeFromTriggeredRules($triggeredRules);
                (new Payment\Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, $errorCode);

                $e = new Exception\BadRequestException($errorCode, null, $data);

                $this->updatePaymentAuthFailed($e);

                throw $e;
            }
        }
    }

    protected function getRiskEngineVersionVariant(Merchant\Entity $merchant)
    {
        $configEntity = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), PaymentConfigType::RISK);

        if ($configEntity != null)
        {
            $config = json_decode($configEntity->config, true);

            if (empty($config[self::SECURE_3D_INTERNATIONAL]) === false)
            {
                return $config[self::SECURE_3D_INTERNATIONAL];
            }
        }

        return $this->app->razorx->getTreatment($merchant->getId(), self::SECURE_3D_INTERNATIONAL, $this->mode);
    }

    protected function getErrorCodeFromTriggeredRules(array $triggeredRules) : string
    {
        if ($this->isFraudDueToWebsiteMismatch($triggeredRules) === true)
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH;
        }
        return ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD;
    }

    private function isFraudDueToWebsiteMismatch(array $triggeredRules) : bool
    {
        if (isset($triggeredRules[Shield::ACTION_BLOCK]) === false)
        {
            return false;
        }

        foreach ($triggeredRules[Shield::ACTION_BLOCK] as $blockRule)
        {
            // https://razorpay.slack.com/archives/C9AKQB8BH/p1591871493389700
            if (in_array($blockRule[Shield::RULE_ID],Shield::RULE_IDS_FOR_FRAUD_WEBSITE_MISMATCH) === true)
            {
                return true;
            }

            if ($blockRule[Shield::RULE_CODE] === Shield::DOMAIN_MISMATCH_BLOCK_NOTIFY_MERCHANT)
            {
                return true;
            }
        }
        return false;
    }
}
