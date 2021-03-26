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
use RZP\Models\Feature\Constants as Feature;

class Shield
{
    protected $request;

    protected $trace;

    protected $shieldClient;

    protected $merchantCore;

    protected $ba;

    protected $repo;

    public function __construct($app)
    {
        $this->request = $app['request'];

        $this->shieldClient = $app['shield'];

        $this->trace = $app['trace'];

        $this->ba = $app['basicauth'];

        $this->repo = $app['repo'];

        $this->merchantCore = new Merchant\Core;

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

    protected function generateShieldPayload(Payment\Entity $payment)
    {
        $payloadDetails = [];

        $this->populateMerchantDetails($payment->merchant, $payloadDetails);

        $this->populatePaymentDetails($payment, $payloadDetails);

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
        $payloadDetails[ShieldConstants::MERCHANT_ID]             = $merchant->getId();
        $payloadDetails[ShieldConstants::MERCHANT_NAME]           = $merchant->getBillingLabel();
        $payloadDetails[ShieldConstants::MERCHANT_EMAIL]          = $merchant->getEmail();
        $payloadDetails[ShieldConstants::MERCHANT_BUSINESS_TYPE]  = $merchant->merchantDetail->getBusinessType();
        $payloadDetails[ShieldConstants::MERCHANT_CATEGORY]       = $merchant->getCategory2();
        $payloadDetails[ShieldConstants::MERCHANT_CATEGORY_CODE]  = (string) $merchant->getCategory();
        $payloadDetails[ShieldConstants::MERCHANT_RISK_THRESHOLD] = $merchant->getRiskThreshold();
        $payloadDetails[ShieldConstants::MERCHANT_WEBSITE]        = $merchant->merchantDetail->getWebsite();
        $payloadDetails[ShieldConstants::MERCHANT_CREATED_AT]     = $merchant->getCreatedAt();
        $payloadDetails[ShieldConstants::MERCHANT_ACTIVATED_AT]   = $merchant->getActivatedAt();
        $payloadDetails[ShieldConstants::MERCHANT_PROMOTER_PAN]   = strtoupper($merchant->merchantDetail->getPromoterPan() ?? '');
        $payloadDetails[ShieldConstants::MERCHANT_GSTIN]          = strtoupper($merchant->merchantDetail->getGstin() ?? '');
        $payloadDetails[ShieldConstants::MERCHANT_BANK_ACCOUNT]   = strtoupper($merchant->merchantDetail->getBankAccountNumber() ?? '');

        $payloadDetails[ShieldConstants::ORG_ID] = $merchant->getOrgId();

        $this->populateWhiteListedDomains($merchant, $payloadDetails);

    }

    protected function populatePaymentDetails(Payment\Entity $payment, array & $payloadDetails)
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

                break;

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

        $paArray = $paymentAnalytics->toArray();
        $payloadDetails[ShieldConstants::RZP_CHECKOUT_LIBRARY] = $paArray[Payment\Analytics\Entity::LIBRARY] ?? null;

        $packageName = $payment->getMetadata(ShieldConstants::PACKAGE_NAME);
        if (empty($packageName) === false)
        {
            $payloadDetails[ShieldConstants::PACKAGE_NAME] = $packageName;
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
}
