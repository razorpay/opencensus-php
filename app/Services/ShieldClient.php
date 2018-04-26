<?php

namespace RZP\Services;

use Requests;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Analytics\Entity as Analytics;

class ShieldClient
{
    const REQUEST_TIMEOUT   = 30; // In secs

    const RULES             = '/rules/';

    const EVALUATE          = '/rules/evaluate';

    const X_RULESET         = 'x-ruleset';

    const CONTENT_TYPE      = 'content-type';

    protected $config;

    protected $baseUrl;

    protected $trace;

    protected $ruleset;

    const PAYMENT_ANALYTICS_KEYS = [
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

    public function createRule(array $input): array
    {
        return $this->sendRequest(self::RULES, Requests::POST, $input);
    }

    public function getRules(): array
    {
        return $this->sendRequest(self::RULES, Requests::GET);
    }

    public function getRuleById(string $id): array
    {
        return $this->sendRequest(self::RULES . $id, Requests::GET);
    }

    public function deleteRuleById(string $id): array
    {
        return $this->sendRequest(self::RULES . $id, Requests::DELETE);
    }

    public function updateRuleById(string $id, array $input): array
    {
        return $this->sendRequest(self::RULES . $id, Requests::PUT, $input);
    }

    public function evaluateRules(array $input): array
    {
        return $this->sendRequest(self::EVALUATE, Requests::POST, $input);
    }

    public function runFraudCheck(Payment\Entity $payment): array
    {
        $paymentRequest = $this->getPaymentProperties($payment);

        return $this->evaluateRules($paymentRequest);
    }

    protected function getPaymentProperties(Payment\Entity $payment): array
    {
        $request = [
            Payment\Entity::MERCHANT_ID => $payment->getMerchantId(),
            'entity_id'                 => $payment->getId(),
            'entity_type'               => $payment->getEntity()
        ];

        $input = [
            Payment\Entity::ID            => $payment->getId(),
            Payment\Entity::AMOUNT        => $payment->getAmount(),
            Payment\Entity::MERCHANT_ID   => $payment->getMerchantId(),
            'merchant_name'               => $payment->merchant->getBillingLabel(),
            'merchant_category'           => $payment->merchant->getCategory2(),
            Payment\Entity::INTERNATIONAL => $payment->isInternational(),
            Payment\Entity::CONTACT       => $payment->getContact(),
            Payment\Entity::EMAIL         => $payment->getEmail(),
            Payment\Entity::CREATED_AT    => $payment->getCreatedAt(),
            Payment\Entity::METHOD        => $payment->getMethod(),
        ];

        $methodParams = $this->fillMethodSpecificDetails($payment);

        $input = array_merge($input, $methodParams);

        if ($payment->hasOrder() === true)
        {
            $input['attempts'] = $payment->order->getAttempts();
        }

        $analytics = $this->getPaymentAnalyticsData($payment);

        $input = array_merge($input, $analytics);

        $request['input'] = $input;

        return $request;
    }

    protected function fillMethodSpecificDetails(Payment\Entity $payment): array
    {
        $methodParams = [];

        $method = $payment->getMethod();

        switch($method)
        {
            case Method::NETBANKING:
                $methodParams[Payment\Entity::BANK] = $payment->getBankName();
                break;

            case Method::WALLET:
                $methodParams[Payment\Entity::WALLET] = strtolower($payment->getWallet());
                break;

            case Method::UPI:
                $methodParams[Payment\Entity::VPA] = $payment->getVpa();
                break;

            case Method::CARD:
                $card = $payment->card;

                $methodParams['card_iin']      = $card->getIin();
                $methodParams['card_network']  = $card->getNetwork();
                $methodParams['card_type']     = $card->getType();
                $methodParams['card_country']  = $card->getCountry();
                $methodParams['card_issuer']   = $card->getIssuer();
                $methodParams['card_name']     = $card->getName();
                break;

        }

        return $methodParams;
    }

    protected function getPaymentAnalyticsData(Payment\Entity $payment): array
    {
        $pa = $payment->analytics;

        // Return if no analytics entity for payment
        if ($pa === null)
        {
            return [];
        }

        $analytics = [];

        foreach (self::PAYMENT_ANALYTICS_KEYS as $key)
        {
            // generates getter function
            $getterName = 'get' . studly_case($key);

            if (method_exists($pa, $getterName) === true)
            {
                $value = $pa->$getterName();

                if (empty($value) === false)
                {
                    $analytics[$key] = $value;
                }
            }
        }

        return $analytics;
    }

    private function sendRequest(string $path, string $method, array $data = []): array
    {
        $headers = $this->getShieldHeaders();

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $this->getAuthHeaders(),
        ];

        $content = '';

        if (empty($data) === false)
        {
             $content = json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        $url = $this->baseUrl . $path;

        try
        {
            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            return $this->parseAndReturnResponse($response);
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

        return [];
    }

    protected function parseAndReturnResponse($res): array
    {
        $code = $res->status_code;
        $responseArray = json_decode($res->body, true);

        if ($code !== 200)
        {
            $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR, $responseArray);
        }

        return $responseArray;
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
