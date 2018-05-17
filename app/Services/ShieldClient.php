<?php

namespace RZP\Services;

use App;
use Requests;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Analytics\Entity as Analytics;

class ShieldClient implements ExternalService
{
    const REQUEST_TIMEOUT   = 30; // In secs

    const RULES_PATH        = '/merchants/{merchant_id}/rules';

    const EVALUATE_PATH     = '/rules/evaluate';

    const ANALYTICS_PATH    = '/rules/analytics';

    const CONTENT_TYPE      = 'content-type';

    const RULES             = 'rules';

    const RULE_ANALYTICS    = 'rule_analytics';

    protected $config;

    protected $baseUrl;

    protected $trace;

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

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->config = $app['config']->get('applications.shield');

        $this->trace = $app['trace'];

        $this->baseUrl = $this->config['url'];
    }

    public function fetchMultiple(string $entity, array $input)
    {
        switch ($entity)
        {
            case self::RULES:
                return $this->getRules($input);

            case self::RULE_ANALYTICS:
                return $this->getRuleAnalytics($input);
        }

        return [];
    }

    public function fetch(string $entity, string $id, array $input)
    {
        switch ($entity)
        {
            case self::RULES:
                return $this->getRuleById($id);
        }

        return [];
    }

    public function createRule(array $input)
    {
        return $this->sendRequest($this->getRulesPath(), Requests::POST, $input);
    }

    public function getRules(array $input)
    {
        return $this->sendRequest($this->getRulesPath(), Requests::GET, $input);
    }

    public function getRuleById(string $id): array
    {
        return $this->sendRequest($this->getRulesPath() . '/' . $id, Requests::GET);
    }

    public function deleteRuleById(string $id): array
    {
        return $this->sendRequest($this->getRulesPath() . '/' . $id, Requests::DELETE);
    }

    public function updateRuleById(string $id, array $input): array
    {
        return $this->sendRequest($this->getRulesPath() . '/' . $id, Requests::PUT, $input);
    }

    public function evaluateRules(array $input): array
    {
        return $this->sendRequest(self::EVALUATE_PATH, Requests::POST, $input);
    }

    public function runFraudCheck(Payment\Entity $payment): array
    {
        $paymentRequest = $this->getPaymentProperties($payment);

        return $this->evaluateRules($paymentRequest);
    }

    public function getRuleAnalytics(array $input): array
    {
        return $this->sendRequest(self::ANALYTICS_PATH, Requests::GET, $input);
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
            case Method::EMI:
                $card = $payment->card;

                $methodParams['card_iin']      = $card->getIin();
                $methodParams['card_network']  = $card->getNetwork();
                $methodParams['card_type']     = $card->getType();
                $methodParams['card_country']  = $card->getCountry();
                $methodParams['card_issuer']   = $card->getIssuer();
                $methodParams['card_name']     = $card->getName();
                $methodParams['card_last4']    = $card->getLast4();
                $methodParams['card_length']   = $card->getLength();
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

        switch ($method)
        {
            case Requests::GET:
                $content = $data;
                break;

            case Requests::POST:
            case Requests::PUT:
                if (empty($data) === false)
                {
                    $content = json_encode($data, JSON_UNESCAPED_SLASHES);
                }
                break;
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
            $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR, ['response' => $responseArray]);
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
            self::CONTENT_TYPE => 'application/json',
        ];
    }

    /**
     * Shield stores all global rules which are create by Admin under the Shared merchant account.
     * This will be changed when we expose Shield entities to Merchants.
     */
    private function getRulesPath(): string
    {
        return str_replace('{merchant_id}', Account::SHARED_ACCOUNT, self::RULES_PATH);
    }

}
