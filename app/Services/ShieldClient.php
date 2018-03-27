<?php

namespace RZP\Services;

use Requests;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Analytics\Entity as Analytics;

class ShieldClient
{
    const RULES = '/rules/';

    const EVALUATE = '/rules/evaluate';

    const X_RULESET = 'x-ruleset';

    const CONTENT_TYPE = 'content-type';

    protected $config;

    protected $baseUrl;

    protected $trace;

    protected $ruleset;

    const CONTEXT_KEYS = [
        Analytics::IP,
        Analytics::CHECKOUT_ID,
        Analytics::USER_AGENT,
        Analytics::LIBRARY,
        Analytics::LIBRARY_VERSION,
        Analytics::PLATFORM,
        Analytics::PLATFORM_VERSION,
        Analytics::REFERER,
        Analytics::BROWSER,
        Analytics::OS,
        Analytics::OS_VERSION,
        Analytics::DEVICE,
    ];

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.shield');

        $this->trace = $app['trace'];

        $this->ruleset = $this->config['ruleset'];

        $this->baseUrl = $this->config['url'];
    }

    public function createRule(array $input)
    {
        return $this->sendRequest(self::RULES, Requests::POST, $input);
    }

    public function getRules()
    {
        return $this->sendRequest(self::RULES, Requests::GET);
    }

    public function getRuleById(string $id)
    {
        return $this->sendRequest(self::RULES . $id, Requests::GET);
    }

    public function deleteRuleById(string $id)
    {
        return $this->sendRequest(self::RULES . $id, Requests::DELETE);
    }

    public function updateRuleById(string $id, array $input)
    {
        return $this->sendRequest(self::RULES . $id, Requests::PUT, $input);
    }

    public function evaluateRules(array $input)
    {
        return $this->sendRequest(self::EVALUATE, Requests::POST, $input);
    }

    public function runFraudCheck(Payment\Entity $payment)
    {
        $paymentRequest = $this->getPaymentProperties($payment);

        return $this->evaluateRules($paymentRequest);
    }

    protected function getPaymentProperties(Payment\Entity $payment)
    {
        $request = [
            'merchant_id' => $payment->getMerchantId(),
            'primary_key' => $payment->getId()
        ];

        $input = [
            'id'                => $payment->getId(),
            'amount'            => $payment->getAmount(),
            'merchant_name'     => $payment->merchant->getBillingLabel(),
            'merchant_category' => $payment->merchant->getCategory2(),
            'international'     => $payment->isInternational(),
            'contact'           => $payment->getContact(),
            'email'             => $payment->getEmail(),
            'created_at'        => $payment->getCreatedAt(),
            'method'            => $payment->getMethod(),

        ];

        $method = $payment->getMethod();

        if ($method === Method::NETBANKING)
        {
            $input['bank'] = $payment->getBankName();
        }

        if ($method === Method::WALLET)
        {
            $input['wallet'] = ucfirst($payment->getWallet());
        }

        if ($method === Method::UPI)
        {
            $input['vpa'] = $payment->getVpa();
        }

        if ($payment->hasCard() === true)
        {
            $input['card_iin'] = $payment->card->getIin();
            $input['card_network'] = $payment->card->getNetwork();
            $input['card_type'] = $payment->card->getType();
            $input['card_country'] = $payment->card->getCountry();
            $input['card_issuer'] = $payment->card->getIssuer();
            $input['international'] = $payment->isInternational();
            $input['card_name'] = $payment->card->getName();
        }

        if ($payment->hasOrder() === true)
        {
            $input['attempts'] = $payment->order->getAttempts();
        }

        $analytics = $this->getPaymentAnalyticsData($payment);

        $input = array_merge($input, $analytics);

        $request['input'] = $input;

        return $request;
    }

    protected function getPaymentAnalyticsData(Payment\Entity $payment)
    {
        $analytics = [];

        $metadata = $payment->getMetadata();

        if (empty($metadata) === true)
        {
            $analytics = $this->fetchPaymentAnalytics($payment);
        }
        else
        {
            // filter metadata for required keys
            foreach (self::CONTEXT_KEYS as $key)
            {
                if (isset($metadata[$key]) === true)
                {
                    $analytics[$key] = $metadata[$key];
                }
            }
        }

        return $analytics;
    }

    protected function fetchPaymentAnalytics(Payment\Entity $payment)
    {
        $pa = $payment->analytics;

        // Return if no analytics entity for payment
        if ($pa === null)
        {
            return [];
        }

        $analytics = [];

        foreach (self::CONTEXT_KEYS as $key)
        {
            // generates getter function
            $getterName = 'get' . studly_case($key);

            if (method_exists($pa, $getterName) === true)
            {
                $getterValue = $pa->$getterName();

                if (empty($getterValue) === false)
                {
                    $analytics[$key] = $getterValue;
                }
            }
        }

        return $analytics;
    }

    private function sendRequest(string $path, string $method, array $data = [])
    {
        $headers = $this->getShieldHeaders();

        $options = [
            'auth' => $this->getAuthHeaders()
        ];

        $content = '';

        if (empty($data) === false)
        {
             $content = json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        $url = $this->baseUrl . $path;

        $responseArr = [];

        try
        {
            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            $responseArr = json_decode($response->body, true);
        }
        catch(\Requests_Exception $e)
        {
            $data = [
                'exception'     => $e->getMessage(),
                'url'           => $url,
                'input'         => $data,
            ];

            $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR, $data);
        }

        return $responseArr;
    }

    private function getAuthHeaders() : array
    {
        return [
            $this->config['auth']['username'],
            $this->config['auth']['password'],
        ];
    }

    private function getShieldHeaders() : array
    {
        return [
            self::X_RULESET    => $this->ruleset,
            self::CONTENT_TYPE => 'application/json',
        ];
    }
}
