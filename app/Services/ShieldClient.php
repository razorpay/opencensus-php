<?php

namespace RZP\Services;

use Requests;

use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payment\Analytics as Analytics;
use RZP\Models\Payment\Method;
use RZP\Trace\TraceCode;

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

        sd($paymentRequest);
        return $this->evaluateRules($paymentRequest);
    }

    protected function getPaymentProperties(Payment\Entity $payment): array
    {
        $request = [
            Payment\Entity::MERCHANT_ID => $payment->getMerchantId(),
            'primary_key'               => $payment->getId()
        ];

        $input = [
<<<<<<< HEAD
            'id'                => $payment->getId(),
            'amount'            => $payment->getAmount(),
            'merchant_id'       => $payment->getMerchantId(),
            'merchant_name'     => $payment->merchant->getBillingLabel(),
            'merchant_category' => $payment->merchant->getCategory2(),
            'international'     => $payment->isInternational(),
            'contact'           => $payment->getContact(),
            'email'             => $payment->getEmail(),
            'created_at'        => $payment->getCreatedAt(),
            'method'            => $payment->getMethod(),
=======
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
>>>>>>> [Shield] minor changes and logPaymentForShield in Risk
        ];

        $method = $payment->getMethod();

        if ($method === Method::NETBANKING)
        {
            $input[Payment\Entity::BANK] = $payment->getBankName();
        }

        if ($method === Method::WALLET)
        {
            $input[Payment\Entity::WALLET] = ucfirst($payment->getWallet());
        }

        if ($method === Method::UPI)
        {
            $input[Payment\Entity::VPA] = $payment->getVpa();
        }

        if ($payment->hasCard() === true)
        {
<<<<<<< HEAD
            $card = $payment->card();

            $input['card_iin'] = $card->getIin();
            $input['card_network'] = $card->getNetwork();
            $input['card_type'] = $card->getType();
            $input['card_country'] = $card->getCountry();
            $input['card_issuer'] = $card->getIssuer();
            $input['card_name'] = $card->getName();
=======
            $card = $payment->card;

            $input['card_iin']      = $card->getIin();
            $input['card_network']  = $card->getNetwork();
            $input['card_type']     = $card->getType();
            $input['card_country']  = $card->getCountry();
            $input['card_issuer']   = $card->getIssuer();
            $input['international'] = $payment->isInternational();
            $input['card_name']     = $card->getName();
>>>>>>> [Shield] minor changes and logPaymentForShield in Risk
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

    protected function getPaymentAnalyticsData(Payment\Entity $payment): array
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
            foreach (self::PAYMENT_ANALYTICS_KEYS as $key)
            {
                if (isset($metadata[$key]) === true)
                {
                    $analytics[$key] = $metadata[$key];
                }
            }
        }

        return $analytics;
    }

    protected function fetchPaymentAnalytics(Payment\Entity $payment): array
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
                $getterValue = $pa->$getterName();

                if (empty($getterValue) === false)
                {
                    $analytics[$key] = $getterValue;
                }
            }
        }

        return $analytics;
    }

    private function sendRequest(string $path, string $method, array $data = []): array
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
