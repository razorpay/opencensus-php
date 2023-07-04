<?php

namespace RZP\Services;

use Carbon\Carbon;
use RZP\Models\Address;
use RZP\Constants\Mode;
use RZP\Constants\Environment;
use RZP\Models\Feature\Constants;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Risk;
use RZP\Services\ShieldClient;
use RZP\Constants\Shield as ShieldConstants;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Payment\Config\Type as PaymentConfigType;
use RZP\Services\Mock\Shield as MockShield;

class Shield
{
    protected $request;

    protected $trace;

    protected $shieldClient;

    protected $merchantCore;

    protected $ba;

    protected $repo;

    protected $config;

    protected $runningUnitTests;

    protected $queue;

    protected $mode;

    protected $app;

    public function __construct($app)
    {
        $this->request = $app['request'];

        $this->shieldClient = $app['shield'];

        $this->trace = $app['trace'];

        $this->ba = $app['basicauth'];

        $this->repo = $app['repo'];

        $this->merchantCore = new Merchant\Core;

        $this->config = $app['config'];

        $this->queue = $app['queue'];

        $this->mode = $app['rzp.mode'] ?? ($app->runningUnitTests() ? Mode::TEST : Mode::LIVE);

        $this->runningUnitTests = $app->runningUnitTests();

        $this->app = $app;

        if ($this->runningUnitTests == true)
        {
            $this->app['shield.mock_service'] = new Mock\Shield($app);
        }
    }


    public function enqueueShieldEvent($event)
    {
        try
        {
            if ($this->runningUnitTests === true)
            {
                $this->app['shield.mock_service']->enqueueShieldEvent($event);
                return;
            }

            $queueName = $this->config->get(ShieldConstants::SHIELD_SQS);

            $event['mode'] = $this->mode ?? Mode::LIVE;

            $this->queue->connection('sqs')->pushRaw(json_encode($event), $queueName);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::SHIELD_SQS_ENQUEUE_FAILED
            );

            if ($this->runningUnitTests === true)
            {
                throw $e;
            }
        }
    }

    public function getRiskAssessment(Payment\Entity $payment, $input = [])
    {
        $this->trace->info(
            TraceCode::FRAUD_DETECTION_STARTED,
            [
                'payment_id' => $payment->getId()
            ]);

        try
        {
            $shieldPayload = $this->generateShieldPayload($payment, $input);

            $response = $this->shieldClient->evaluateRules($shieldPayload);

            $riskData = $this->parseShieldResponse($response);

            $riskData[ShieldConstants::EVALUATION_PAYLOAD] = $shieldPayload;

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
                $riskData[Risk\Entity::FRAUD_TYPE]          = Risk\Type::CONFIRMED;
                $riskData[Risk\Entity::REASON]              = Risk\RiskCode::PAYMENT_CONFIRMED_FRAUD_BY_SHIELD;
                $riskData[Risk\Entity::RISK_SCORE]          = $response[ShieldConstants::MAXMIND_SCORE];
                $riskData[ShieldConstants::TRIGGERED_RULES] = $response[ShieldConstants::TRIGGERED_RULES] ?? [];
                break;

            case ShieldConstants::ACTION_REVIEW:
                $riskData[Risk\Entity::FRAUD_TYPE]            = Risk\Type::SUSPECTED;
                $riskData[Risk\Entity::REASON]                = Risk\RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_SHEILD;
                $riskData[Risk\Entity::RISK_SCORE]            = $response[ShieldConstants::MAXMIND_SCORE];
                $riskData[ShieldConstants::TRIGGERED_RULES]   = $response[ShieldConstants::TRIGGERED_RULES] ?? [];

                break;

            default:
                $riskData[Risk\Entity::RISK_SCORE] = $response[ShieldConstants::MAXMIND_SCORE];

                break;
        }

        return $riskData;

    }

    protected function generateShieldPayload(Payment\Entity $payment, $input = [])
    {
        $payloadDetails = [];

        $this->populateMerchantDetails($payment->merchant, $payloadDetails);

        $this->populatePaymentDetails($payment, $payloadDetails, $input);

        $this->populatePaymentRequestDetails($payment, $payloadDetails);

        $payloadDetails[ShieldConstants::PAYMENT_PRODUCT] = $this->getPaymentProduct($payment);

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
        $payloadDetails[ShieldConstants::MERCHANT_ID]                = $merchant->getId();
        $payloadDetails[ShieldConstants::MERCHANT_NAME]              = $merchant->getBillingLabel();
        $payloadDetails[ShieldConstants::MERCHANT_EMAIL]             = $merchant->getEmail();
        $payloadDetails[ShieldConstants::MERCHANT_CATEGORY]          = $merchant->getCategory2();
        $payloadDetails[ShieldConstants::MERCHANT_CATEGORY_CODE]     = (string) $merchant->getCategory();
        $payloadDetails[ShieldConstants::MERCHANT_RISK_THRESHOLD]    = $merchant->getRiskThreshold();
        $payloadDetails[ShieldConstants::MERCHANT_BUSINESS_TYPE]     = $merchant->merchantDetail->getBusinessType();
        $payloadDetails[ShieldConstants::MERCHANT_BUSINESS_CATEGORY] = $merchant->merchantDetail->getBusinessCategory();

        if (isset($merchant->merchantDetail) === true)
        {
            $payloadDetails[ShieldConstants::MERCHANT_WEBSITE]    = $merchant->merchantDetail->getWebsite();
        }

        $payloadDetails[ShieldConstants::MERCHANT_CREATED_AT]     = $merchant->getCreatedAt();
        $payloadDetails[ShieldConstants::MERCHANT_ACTIVATED_AT]   = $merchant->getActivatedAt();
        $payloadDetails[ShieldConstants::MERCHANT_PROMOTER_PAN]   = strtoupper($merchant->merchantDetail->getPromoterPan() ?? '');
        $payloadDetails[ShieldConstants::MERCHANT_GSTIN]          = strtoupper($merchant->merchantDetail->getGstin() ?? '');
        $payloadDetails[ShieldConstants::MERCHANT_BANK_ACCOUNT]   = strtoupper($merchant->merchantDetail->getBankAccountNumber() ?? '');
        $payloadDetails[ShieldConstants::APPS_EXEMPT_RISK_CHECK]  = $merchant->isFeatureEnabled(Constants::APPS_EXTEMPT_RISK_CHECK);

        $payloadDetails[ShieldConstants::ORG_ID] = $merchant->getOrgId();

        $this->populateEarlySettlementDetails($merchant, $payloadDetails);

        $this->populateWhiteListedDomains($merchant, $payloadDetails);

        $this->populateSecure3dInternationalFlag($merchant, $payloadDetails);

        if (isset($merchant->merchantBusinessDetail) === true)
        {
            $payloadDetails[ShieldConstants::MERCHANT_WHITELISTED_APP_URLS] = $merchant->merchantBusinessDetail->getAppUrls();
        }

    }

    protected function populatePaymentDetails(Payment\Entity $payment, array & $payloadDetails, $input = [])
    {
        $payloadDetails[ShieldConstants::ID]            = $payment->getId();
        $payloadDetails[ShieldConstants::AMOUNT]        = $payment->getAmount();
        // BaseAmount is set in processCurrencyConversions (Payment/Processor/Authorize.php) before fraud check is initiated
        $payloadDetails[ShieldConstants::BASE_AMOUNT]   = $payment->getBaseAmount();
        $payloadDetails[ShieldConstants::CURRENCY]      = $payment->getCurrency();
        $payloadDetails[ShieldConstants::RECURRING]     = $payment->isRecurring();
        $payloadDetails[ShieldConstants::CONTACT]       = $payment->getContact();
        $payloadDetails[ShieldConstants::INTERNATIONAL] = $payment->isInternational();
        $payloadDetails[ShieldConstants::CALLBACK_URL]  = $payment->getCallbackUrl();

        if ($payment->isRecurring() === true)
        {
            $payloadDetails[ShieldConstants::RECURRING_TYPE] = $payment->getRecurringType();
        }

        $paymentToken = $payment->getGlobalOrLocalTokenEntity();

        if (is_null($paymentToken) === false)
        {
            $payloadDetails[ShieldConstants::TOKEN_ID] = $paymentToken->getId();

            $tokenMaxAmount = $paymentToken->getMaxAmount();

            if (is_null($tokenMaxAmount) === false)
            {
                $payloadDetails[ShieldConstants::TOKEN_MAX_AMOUNT] = $tokenMaxAmount;
            }
        }

        $payloadDetails[ShieldConstants::EMAIL] =
            (($payment->isCustomerMailAbsent() === false) ? $payment->getEmail() : ShieldConstants::DEFAULT_EMAIL);

        // add payment method details
        $payloadDetails[ShieldConstants::METHOD] = $payment->getMethod();

        if (isset($input[Payment\Entity::BILLING_ADDRESS]) === true)
        {
            $billingAddress = $input[Address\Type::BILLING_ADDRESS];

            $payloadDetails[ShieldConstants::BILLING_ADDRESS_LINE_1]      = isset($billingAddress[Address\Entity::LINE1]) ? $billingAddress[Address\Entity::LINE1] : null;
            $payloadDetails[ShieldConstants::BILLING_ADDRESS_LINE_2]      = isset($billingAddress[Address\Entity::LINE2]) ? $billingAddress[Address\Entity::LINE2] : null ;
            $payloadDetails[ShieldConstants::BILLING_ADDRESS_CITY]        = isset($billingAddress[Address\Entity::CITY])  ? $billingAddress[Address\Entity::CITY] : null ;
            $payloadDetails[ShieldConstants::BILLING_ADDRESS_POSTAL_CODE] = isset($billingAddress['postal_code']) ? $billingAddress['postal_code'] : null ;

            $billingState = isset($billingAddress[Address\Entity::STATE]) ? $billingAddress[Address\Entity::STATE] : null;

            if (strlen($billingState) <= 4)
            {
                $payloadDetails[ShieldConstants::BILLING_ADDRESS_STATE] = strtoupper($billingState);
            }

            $billingCountry = isset($billingAddress[Address\Entity::COUNTRY]) ? $billingAddress[Address\Entity::COUNTRY] : null ;

            if (strlen($billingCountry) === 2)
            {
                $payloadDetails[ShieldConstants::BILLING_ADDRESS_COUNTRY] = strtoupper($billingCountry);
            }
        }

        switch ($payloadDetails[ShieldConstants::METHOD])
        {
            case Payment\Method::NETBANKING:
                $payloadDetails[ShieldConstants::BANK] = $payment->getBankName();

                break;

            case Payment\Method::PAYLATER:
            case Payment\Method::WALLET:
                $payloadDetails[ShieldConstants::WALLET] = strtolower($payment->getWallet());

                break;

            case Payment\Method::UPI:
                $payloadDetails[ShieldConstants::VPA]      = $payment->getVpa();
                $payloadDetails[ShieldConstants::UPI_TYPE] = $payment->getMetadata('flow') ?? 'collect';

                break;

            case Payment\Method::CARD:
                if ($payment->isGooglePayCard() === true)
                {
                    break;
                }
            case Payment\Method::EMI:
                $card = $payment->card;

                $payloadDetails[ShieldConstants::CARD_FP]           = $card->getGlobalFingerPrint();
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

                if($payment->isInternational() === true)
                {
                    if(isset($input['card']) === true)
                    {
                        $payloadDetails[ShieldConstants::CARD_NUMBER]      = $input['card']['number'];
                    }
                    else
                    {
                        $this->trace->info(TraceCode::SHIELD_CARD_DATA_MISSING, ['payment_id' => $payment->getId()]);
                    }
                }
                break;

        }
    }

    protected function populateSecure3dInternationalFlag(Merchant\Entity $merchant, array & $payloadDetails)
    {
        $configEntity = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), PaymentConfigType::RISK);

        if ($configEntity != null)
        {
            $config = json_decode($configEntity->config, true);

            if (empty($config[ShieldConstants::SECURE_3D_INTERNATIONAL]) === false)
            {
                $payloadDetails[ShieldConstants::SECURE_3D_INTERNATIONAL] = $config[ShieldConstants::SECURE_3D_INTERNATIONAL];
            }
        }
    }

    protected function populatePaymentRequestDetails(Payment\Entity $payment, array & $payloadDetails)
    {
        $payloadDetails[ShieldConstants::ACCEPT_LANGUAGE] = $this->request->header('Accept-Language');

        $shieldMetadata = $payment->getMetadata('shield');

        if ((is_array($shieldMetadata) === true) && (isset($shieldMetadata['fhash']) === true)) {
            $payloadDetails[ShieldConstants::FRONTEND_FP_HASH] = $shieldMetadata['fhash'];
        }

        $paymentAnalytics = $payment->getMetadata('payment_analytics');

        if (is_null($paymentAnalytics) === true)
        {
            $paymentAnalytics = $payment->analytics;

            if (is_null($paymentAnalytics) === true)
            {
                return;
            }
        }

        $payloadDetails[ShieldConstants::IP]                   = $paymentAnalytics->getIp();
        $payloadDetails[ShieldConstants::CHECKOUT_ID]          = $paymentAnalytics->getCheckoutId();
        $payloadDetails[ShieldConstants::USER_AGENT]           = $paymentAnalytics->getUserAgent();
        $payloadDetails[ShieldConstants::REFERER]              = $paymentAnalytics->getReferer();
        $payloadDetails[ShieldConstants::BROWSER]              = $paymentAnalytics->getBrowser();
        $payloadDetails[ShieldConstants::BROWSER_VERSION]      = $paymentAnalytics->getBrowserVersion();
        $payloadDetails[ShieldConstants::OS]                   = $paymentAnalytics->getOs();
        $payloadDetails[ShieldConstants::OS_VERSION]           = $paymentAnalytics->getOsVersion();
        $payloadDetails[ShieldConstants::DEVICE]               = $paymentAnalytics->getDevice();
        $payloadDetails[ShieldConstants::ATTEMPTS]             = $paymentAnalytics->getAttempts();
        $payloadDetails[ShieldConstants::PLATFORM]             = $paymentAnalytics->getPlatform();
        $payloadDetails[ShieldConstants::PLATFORM_VERSION]     = $paymentAnalytics->getPlatformVersion();
        $payloadDetails[ShieldConstants::INTEGRATION]          = $paymentAnalytics->getIntegration();
        $payloadDetails[ShieldConstants::INTEGRATION_VERSION]  = $paymentAnalytics->getIntegrationVersion();

        $paArray = $paymentAnalytics->toArray();
        $payloadDetails[ShieldConstants::RZP_CHECKOUT_LIBRARY] = $paArray[Payment\Analytics\Entity::LIBRARY] ?? null;

        $packageName = $payment->getMetadata(ShieldConstants::PACKAGE_NAME);
        if (empty($packageName) === false)
        {
            $payloadDetails[ShieldConstants::PACKAGE_NAME] = $packageName;
        }

        $virtualDeviceId = $paymentAnalytics->getVirtualDeviceId();
        if (empty($virtualDeviceId) === false)
        {
            $payloadDetails[ShieldConstants::VIRTUAL_DEVICE_ID] = $virtualDeviceId;
        }

        if ($payloadDetails[ShieldConstants::INTEGRATION] === ShieldConstants::SHOPIFY and
            $payloadDetails[ShieldConstants::INTEGRATION_VERSION] === ShieldConstants::SHOPIFY_PAYMENT_APP)
        {
            $notes = $payment->getNotes();

            $this->trace->info(
                TraceCode::FRAUD_DETECTION_PAYMENT_NOTES_DETAILS,
                [
                    'payment_id' => $payment->getId(),
                    'notes'      => $notes,
                ]);

            if (isset($notes[ShieldConstants::DOMAIN]) === true)
            {
                $payloadDetails[ShieldConstants::ORDER_DOMAIN] = $notes[ShieldConstants::DOMAIN];
            }

            if (isset($notes[ShieldConstants::CANCEL_URL]) === true)
            {
                $payloadDetails[ShieldConstants::ORDER_CANCEL_URL] = $notes[ShieldConstants::CANCEL_URL];
            }

            if (isset($notes[ShieldConstants::REFERER_URL]) === true)
            {
                $payloadDetails[ShieldConstants::ORDER_REFERER_URL]  = $notes[ShieldConstants::REFERER_URL];
            }
        }
    }

    protected function populateWhiteListedDomains(Merchant\Entity $merchant, array & $payloadDetails)
    {
        $payloadDetails[ShieldConstants::MERCHANT_WHITELISTED_DOMAINS] = (array) $merchant->getWhitelistedDomains();
        /*
            Requirement:
                Send partner whitelisted domains, if applicable, along with merchant whitelisted domains

            If payment is driven by a partner then,
                Collect the partner whitelisted domains and record in partner_urls

            If payment is not driven by a partner then,
                Get all affiliated partners
                For all partners of type -> ["Aggregator", "FullyManaged", "PurePlatform"] collect the whitelisted domains
                and record in partners urls

        */

        $partnerMerchantId = $this->ba->getPartnerMerchantId();
        $isPaymentInitiatedByPartner = ((is_null($partnerMerchantId) === false) and ($partnerMerchantId != $merchant->getId()));

        $partnerWhitelistedDomains = [];
        $partnerIds = [];

        if ($isPaymentInitiatedByPartner === true)
        {
            $partnerMerchant = $this->repo->merchant->find($partnerMerchantId);

            $partnerWhitelistedDomains[$partnerMerchant->getId()] = (array) $partnerMerchant->getWhitelistedDomains();
        }
        else
        {
            $partnerMerchants = $this->merchantCore->fetchAffiliatedPartners($merchant->getId());

            foreach ($partnerMerchants as $partnerMerchant)
            {
                if (($partnerMerchant->isAggregatorPartner() === true) or
                    ($partnerMerchant->isFullyManagedPartner() === true) or
                    ($partnerMerchant->isPurePlatformPartner() === true))
                {
                    $partnerWhitelistedDomains[$partnerMerchant->getId()] = (array) $partnerMerchant->getWhitelistedDomains();
                    $partnerIds[] = $partnerMerchant->getId();
                }
            }
        }

        $payloadDetails[ShieldConstants::IS_PARTNER_INITIATED_PAYMENT] = $isPaymentInitiatedByPartner;

        $payloadDetails[ShieldConstants::PARTNER_WHITELISTED_DOMAINS] = $partnerWhitelistedDomains;

        $payloadDetails[ShieldConstants::PARTNER_IDS] = $partnerIds;
    }

    protected function getPaymentProduct(Payment\Entity $payment)
    {
        $product = ShieldConstants::PRODUCT_PAYMENT_GATEWAY;

        $paymentLinkId = $payment->getPaymentLinkId();

        $authType = $payment->getAuthType();
        $receiverType = $payment->getReceiverType();

        $invoiceType = '';
        $invoiceEntityType = '';

        $method = $payment->getMethod();

        if ($payment->hasInvoice() === true)
        {
            $invoiceType = $payment->invoice->getType();
            $invoiceEntityType = $payment->invoice->getEntityType();
        }
        if (($invoiceType === 'link') and (empty($invoiceEntityType) === true))
        {
            $product = ShieldConstants::PRODUCT_PAYMENT_LINKS;
        }
        else if ($invoiceType === 'invoice')
        {
            $product = ShieldConstants::PRODUCT_PAYMENT_INVOICES;
        }
        else if ($invoiceType === 'ecod')
        {
            $product = ShieldConstants::PRODUCT_PAYMENT_EPOS;
        }
        else if (is_null($paymentLinkId) === false)
        {
            $product = ShieldConstants::PRODUCT_PAYMENT_LINKS;
        }
        else if ($method === 'transfer')
        {
            $product = ShieldConstants::PRODUCT_PAYMENT_ROUTE;
        }
        else if ((empty($receiverType) === false) and (in_array($receiverType, ['bank_account', 'qr_code', 'vpa']) === true))
        {
            $product = ShieldConstants::PRODUCT_PAYMENT_SMART_COLLECT;
        }

        return $product;
    }

    protected function populateEarlySettlementDetails(Merchant\Entity $merchant, array & $payloadDetails)
    {
        $esFeatureFlags = [
            Feature::ES_ON_DEMAND,
            Feature::ES_ON_DEMAND_RESTRICTED,
            Feature::ES_AUTOMATIC,
            Feature::ES_AUTOMATIC_THREE_PM];

        $isEsEnabled = false;

        foreach ($esFeatureFlags as $esFeatureFlag)
        {
            $isEsEnabled = $merchant->isFeatureEnabled($esFeatureFlag);

            if ($isEsEnabled === true)
            {
                break;
            }
        }

        $payloadDetails[ShieldConstants::EARLY_SETTLEMENT_ENABLED] = $isEsEnabled;
    }
}
