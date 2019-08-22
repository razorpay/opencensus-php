<?php

namespace RZP\Services;

use Carbon\Carbon;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Risk;
use RZP\Services\ShieldClient;
use RZP\Constants\Shield as ShieldConstants;
use Razorpay\Trace\Logger as Trace;

class Shield
{
    protected $request;

    protected $trace;

    protected $shieldClient;

    public function __construct($app)
    {
        $this->request = $app['request'];

        $this->shieldClient = $app['shield'];

        $this->trace = $app['trace'];

    }

    public function getRiskAssessment(Payment\Entity $payment)
    {
        $this->trace->info(
            TraceCode::FRAUD_DETECTION_STARTED,
            [
                'payment_id' => $payment->getId()
            ]);

        try
        {
            $shieldPayload = $this->generateShieldPayload($payment);

            $response = $this->shieldClient->evaluateRules($shieldPayload);

            $riskData = $this->parseShieldResponse($response);

            $this->trace->info(
                TraceCode::FRAUD_DETECTION_DONE,
                [
                    'payment_id' => $payment->getId()
                ]);

            return $riskData;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::FRAUD_DETECTION_FAILED,
                [
                    'payment_id' => $payment->getId(),
                ]
            );

            $this->trace->count(Payment\Metric::SHIELD_FRAUD_DETECTION_FAILED);

            throw $e;
        }
    }

    protected function parseShieldResponse($response)
    {
        $riskData = [];

        $recommendedAction = $response[ShieldConstants::ACTION_KEY];

        switch ($recommendedAction)
        {
            case ShieldConstants::ACTION_BLOCK:
                $riskData[Risk\Entity::FRAUD_TYPE] = Risk\Type::CONFIRMED;
                $riskData[Risk\Entity::REASON]     = Risk\RiskCode::PAYMENT_CONFIRMED_FRAUD_BY_SHIELD;

                break;

            case ShieldConstants::ACTION_REVIEW:
                $riskData[Risk\Entity::FRAUD_TYPE] = Risk\Type::SUSPECTED;
                $riskData[Risk\Entity::REASON]     = Risk\RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_SHEILD;

                break;

            default:
                break;
        }

        return $riskData;

    }

    protected function generateShieldPayload(Payment\Entity $payment)
    {
        $payloadDetails = [];

        $this->populateMerchantDetails($payment->merchant, $payloadDetails);

        $this->populatePaymentDetails($payment, $payloadDetails);

        $this->populatePaymentRequestDetails($payment, $payloadDetails);

        $payloadDetails[ShieldConstants::CREATED_AT] = Carbon::now()->getTimestamp();

        $shieldPayload = [
            ShieldConstants::MERCHANT_ID => $payment->getMerchantId(),
            ShieldConstants::ENTITY_TYPE => $payment->getEntity(),
            ShieldConstants::ENTITY_ID   => $payment->getId(),
            ShieldConstants::INPUT       => $payloadDetails,
        ];

        return $shieldPayload;
    }

    protected function populateMerchantDetails(Merchant\Entity $merchant, array & $payloadDetails)
    {
        $payloadDetails[ShieldConstants::MERCHANT_ID]             = $merchant->getId();
        $payloadDetails[ShieldConstants::MERCHANT_NAME]           = $merchant->getBillingLabel();
        $payloadDetails[ShieldConstants::MERCHANT_CATEGORY]       = $merchant->getCategory2();
        $payloadDetails[ShieldConstants::MERCHANT_CATEGORY_CODE]  = (string) $merchant->getCategory();
        $payloadDetails[ShieldConstants::MERCHANT_RISK_THRESHOLD] = $merchant->getRiskThreshold();
        $payloadDetails[ShieldConstants::MERCHANT_WEBSITE]        = $merchant->merchantDetail->getWebsite();
    }

    protected function populatePaymentDetails(Payment\Entity $payment, array & $payloadDetails)
    {
        $payloadDetails[ShieldConstants::ID]            = $payment->getId();
        $payloadDetails[ShieldConstants::AMOUNT]        = $payment->getAmount();
        $payloadDetails[ShieldConstants::CURRENCY]      = $payment->getCurrency();
        $payloadDetails[ShieldConstants::RECURRING]     = $payment->isRecurring();
        $payloadDetails[ShieldConstants::CONTACT]       = $payment->getContact();
        $payloadDetails[ShieldConstants::INTERNATIONAL] = $payment->isInternational();

        $payloadDetails[ShieldConstants::EMAIL] =
            (($payment->isCustomerMailAbsent() === false) ? $payment->getEmail() : ShieldConstants::DEFAULT_EMAIL);

        // add payment method details
        $payloadDetails[ShieldConstants::METHOD] = $payment->getMethod();

        switch ($payloadDetails[ShieldConstants::METHOD])
        {
            case Payment\Method::NETBANKING:
                $payloadDetails[ShieldConstants::BANK] = $payment->getBankName();

                break;

            case Payment\Method::WALLET:
                $payloadDetails[ShieldConstants::WALLET] = strtolower($payment->getWallet());

                break;

            case Payment\Method::UPI:
                $payloadDetails[ShieldConstants::VPA] = $payment->getVpa();

                break;

            case Payment\Method::CARD:
            case Payment\Method::EMI:
                $card = $payment->card;

                $payloadDetails[ShieldConstants::CARD_IIN]          = $card->getIin();
                $payloadDetails[ShieldConstants::CARD_NETWORK]      = $card->getNetworkCode();
                $payloadDetails[ShieldConstants::CARD_TYPE]         = $card->getType();
                $payloadDetails[ShieldConstants::CARD_COUNTRY]      = $card->getCountry();
                $payloadDetails[ShieldConstants::CARD_ISSUER]       = $card->getIssuer();
                $payloadDetails[ShieldConstants::CARD_NAME]         = $card->getName();
                $payloadDetails[ShieldConstants::CARD_LAST4]        = $card->getLast4();
                $payloadDetails[ShieldConstants::CARD_LENGTH]       = $card->getLength();
                $payloadDetails[ShieldConstants::CARD_EXPIRY_MONTH] = $card->getExpiryMonth();
                $payloadDetails[ShieldConstants::CARD_EXPIRY_YEAR]  = $card->getExpiryYear();

                break;

        }
    }

    protected function populatePaymentRequestDetails(Payment\Entity $payment, array & $payloadDetails)
    {
        $payloadDetails[ShieldConstants::ACCEPT_LANGUAGE] = $this->request->header('Accept-Language');

        $paymentAnalytics = $payment->getMetadata("payment_analytics");

        if (is_null($paymentAnalytics) === true)
        {
            return;
        }

        $payloadDetails[ShieldConstants::IP]               = $paymentAnalytics->getIp();
        $payloadDetails[ShieldConstants::USER_AGENT]       = $paymentAnalytics->getUserAgent();
        $payloadDetails[ShieldConstants::REFERER]          = $paymentAnalytics->getReferer();
        $payloadDetails[ShieldConstants::BROWSER]          = $paymentAnalytics->getBrowser();
        $payloadDetails[ShieldConstants::BROWSER_VERSION]  = $paymentAnalytics->getBrowserVersion();
        $payloadDetails[ShieldConstants::OS]               = $paymentAnalytics->getOs();
        $payloadDetails[ShieldConstants::OS_VERSION]       = $paymentAnalytics->getOsVersion();
        $payloadDetails[ShieldConstants::DEVICE]           = $paymentAnalytics->getDevice();
        $payloadDetails[ShieldConstants::ATTEMPTS]         = $paymentAnalytics->getAttempts();
    }
}
