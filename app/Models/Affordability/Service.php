<?php

namespace RZP\Models\Affordability;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Models\Emi\Service as EmiService;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Services\Dcs\Features\Constants as DcsConstants;
use RZP\Models\Merchant\Methods\Core as MethodsCore;
use RZP\Models\Payment\Method as PaymentMethod;
use RZP\Models\Merchant\Checkout;
use RZP\Models\Offer\Core as OfferCore;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Trace\TraceCode;
use stdClass;
use Requests_Exception;
use RZP\Exception;
use RZP\Models\Pricing;

class Service extends Base\Service
{
    public const FETCH_WIDGET_ENDPOINT = "/v1/widget/details";
    public const UPDATE_WIDGET_TRIAL_PERIOD = "/v1/widget/trial_period";

    public const X_REQUEST_TASK_ID        = 'X-Razorpay-TaskId';
    public const X_PASSPORT_JWT_V1        = 'X-Passport-JWT-V1';
    public const CONTENT_TYPE_HEADER      = 'Content-Type';
    public const AUTHORIZATION            = 'Authorization';
    public const CONTENT_TYPE             = 'application/json';

    /** @var Validator */
    private $validator;

    protected $config;

    public function __construct(Validator $validator)
    {
        parent::__construct();

        $this->config = $this->app['config']->get('applications.affordability');

        $this->mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $this->validator = $validator;
    }

    public function fetchSuite(array $input): array
    {
        $this->validator->validateInput('fetch', $input);

        $this->findAndSetMerchantByKey($input['key']);

        $data = [];

        if ($this->isEnabled($data) === false) {
            return $data;
        }

        foreach($input['components'] as $component)
        {
            $functionName = 'fetch' . ucfirst(camel_case($component)) . 'Component';

            if (method_exists($this, $functionName))
            {
                $this->{$functionName}($data);
            }
        }

        return $data;
    }

    /**
     * Check if affordability widget feature is enabled for the merchant.
     *
     * @param array $data
     *
     * @return bool
     */
    protected function isEnabled(array &$data): bool
    {
        $data['enabled'] = $this->merchant->isAtLeastOneFeatureEnabled([Features::AFFORDABILITY_WIDGET,
                                                                        DcsConstants::AffordabilityWidgetSet,]);

        return $data['enabled'];
    }

    protected function fetchCardlessEmiComponent(array &$data): void
    {
        $providers = [];

        if ($this->merchant->methods->isCardlessEmiEnabled() === true)
        {
            $providers = (new MethodsCore())->getProviders($this->merchant, PaymentMethod::CARDLESS_EMI);

            $providers = $this->formatProviders($providers, CardlessEmi::MIN_AMOUNTS);
        }

        $data['entities']['cardless_emi']['providers'] = $this->formatAsDictionary($providers);
    }

    protected function fetchEmiComponent(array &$data): void
    {
        $emiOptions = [];

        if ($this->merchant->methods->isEmiEnabled() === true)
        {
            $emiOptions = (new EmiService())->getEmiPlansAndOptions()['options'];
        }

        $items = [];
        foreach ($emiOptions as $bank => $options) {
            $items[$bank]['values'] = $options;
        }

        $data['entities']['emi']['items'] = $this->formatAsDictionary($items);
    }

    protected function fetchOffersComponent(array &$data): void
    {
        $data['entities']['offers']['items'] = (new OfferCore())->fetchOffersForAffordability($this->merchant->getId());
    }

    protected function fetchPaylaterComponent(array &$data): void
    {
        $providers = [];

        if ($this->merchant->methods->isPayLaterEnabled() === true)
        {
            $providers = (new MethodsCore())->getProviders($this->merchant, PaymentMethod::PAYLATER);

            $providers = $this->formatProviders($providers, PayLater::MIN_AMOUNTS);
        }

        $data['entities']['paylater']['providers'] = $this->formatAsDictionary($providers);
    }

    protected function fetchOptionsComponent(array &$data): void
    {
        $data['options']['theme']['color'] = $this->merchant->getBrandColor();

        $data['options']['image'] = $this->merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE);

        $data['options']['white_label'] = $this->merchant->isFeatureEnabled(Features::AFFORDABILITY_WIDGET_WHITE_LABEL);
    }

    /**
     * Ensures empty associative arrays "[]" show up as empty dictionaries "{}"
     * when run through json_encode().
     *
     * @param array $response
     *
     * @return stdClass
     */
    protected function formatAsDictionary(array $response): stdClass
    {
        return (object) $response;
    }

    /**
     * @param string $keyId Public Key of the Merchant
     */
    private function findAndSetMerchantByKey(string $keyId): void
    {
        $key = $this->repo->key->findByPublicId($keyId);

        $this->merchant = $key->merchant;

        // Base/Service fetches merchant from auth. Removing this gives us null value error on $this->merchant.
        $this->auth->setMerchant($this->merchant);
    }

    /**
     * Format CardlessEmi & PayLater providers response.
     *
     * @param array $providers  CardlessEmi (or) PayLater providers
     * @param array $minAmounts Minimum order/transaction amount for each provider
     *
     * @return array
     */
    protected function formatProviders(array $providers, array $minAmounts): array
    {
        $response = [];

        foreach ($providers as $provider => $enabled) {
            $response[$provider] = [
                'enabled' => $enabled,
                'min_amount' => $minAmounts[$provider] ?? null,
            ];
        }

        return $response;
    }

    public function getWidgetDetails()
    {
        $this->merchant = $this->auth->getMerchant();

        $url = $this->getBaseUrl() . self::FETCH_WIDGET_ENDPOINT;

        try
        {
            $response = Requests::get($url, $this->getRequestHeaders());
        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::FETCH_WIDGET_API_REQUEST_FAILED);

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        $response = $this->formatResponse($response);

        $response['pricing'] = $this->getAffordabilityWidgetPricingForMerchant();

        return $response;
    }

    public function updateWidgetTrialPeriod($input)
    {
        $url = $this->getBaseUrl() . self::UPDATE_WIDGET_TRIAL_PERIOD;

        try
        {
            $response = Requests::put($url, $this->getRequestHeaders(), json_encode($input));
        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::UPDATE_WIDGET_TRIAL_PERIOD_FAILED);

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        $response = $this->formatResponse($response);

        return $response;
    }

    protected function getBaseUrl(): string
    {
        return $this->config['url'];
    }

    protected function getInternalAuthToken(): string
    {
        $config = $this->app['config']->get('applications.affordability');
        $username = 'rzp_' . $this->mode;
        $password = $config['service_secret'];
        return base64_encode("{$username}:{$password}");
    }

    protected function getRequestHeaders(): array
    {
        $jwt = $this->auth->getPassportJwt($this->getBaseUrl());

        return [
            self::CONTENT_TYPE_HEADER  => self::CONTENT_TYPE,
            self::AUTHORIZATION => 'Basic '. $this->getInternalAuthToken(),
            self::X_PASSPORT_JWT_V1 => $jwt,
            self::X_REQUEST_TASK_ID => $this->app['request']->getTaskId(),
        ];
    }

    public function getAffordabilityWidgetPricingForMerchant(): array
    {
        // Merchant's pricing plan id
        $pricingPlanId = $this->merchant->getPricingPlanId();

        $widgetPricingRules = $this->repo->pricing->getPricingRulesByPlanIdProductAndFeatureWithoutOrgId(
            $pricingPlanId,
            Product::PRIMARY,
            PricingFeature::AFFORDABILITY_WIDGET
        );

        if ($widgetPricingRules->isEmpty() === false and count($widgetPricingRules) > 0)
        {
            $rate = $widgetPricingRules[0][Pricing\Entity::FIXED_RATE];
        }

        $defaultPlanId = Pricing\Fee::DEFAULT_AFFORDABILITY_WIDGET_PLAN_ID;

        $defaultWidgetPricingRules = $this->repo->pricing->getPricingRulesByPlanIdProductAndFeatureWithoutOrgId(
            $defaultPlanId,
            Product::PRIMARY,
            PricingFeature::AFFORDABILITY_WIDGET
        );

        if ($defaultWidgetPricingRules->isEmpty() === false and count($defaultWidgetPricingRules) > 0)
        {
            $defaultRate = $defaultWidgetPricingRules[0][Pricing\Entity::FIXED_RATE];
        }

        if(!isset($defaultRate) and !isset($rate))
        {
            throw new Exception\ServerErrorException('No Pricing Defined for Affordability Widget',
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        if(isset($defaultRate) and !isset($rate))
        {
            $rate = $defaultRate;
        }

        $response = [];

        $response['rate'] = $rate;
        if($rate < $defaultRate)
        {
            $response['default'] = $defaultRate;
        }

        return $response;
    }

    /**
     * @param $response
     * @return mixed
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    protected function formatResponse($response)
    {
        if ($response->status_code >= 500) {

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);

        } else if ($response->status_code >= 400) {

            $error = json_decode($response->body);
            $errorDescription = $error->error->description;

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null, $errorDescription);
        }

        if ($response->body === "null" or $response->body === '') {
            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        return json_decode($response->body, true);
    }
}
