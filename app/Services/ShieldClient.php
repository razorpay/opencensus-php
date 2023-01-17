<?php

namespace RZP\Services;

use App;

use RZP\Constants\Mode;
use RZP\Constants\Shield as ShieldConstants;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use RZP\Http\Request\Requests;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Analytics\Entity as Analytics;

class ShieldClient implements ExternalService
{
    const RULES_PATH                        = '/merchants/{merchant_id}/rules';
    const EVALUATE_PATH                     = '/rules/evaluate';
    const ANALYTICS_PATH                    = '/rules/analytics';
    const RISKS_PATH                        = '/merchants/{merchant_id}/risks';
    const RISK_LOGS_PATH                    = '/risk/logs';
    const LISTS_PATH                        = '/merchants/{merchant_id}/lists';
    const LIST_ITEMS_PATH                   = '/merchants/{merchant_id}/lists/{list_id}/list_items';
    const RISK_THRESHOLD_CONFIGS_PATH       = '/risk/threshold/configs';
    const MERCHANT_RISK_THRESHOLDS_PATH     = '/merchant/risk/thresholds';
    const CONTENT_TYPE                      = 'content-type';
    const RULES                             = 'rules';
    const RULE_ANALYTICS                    = 'rule_analytics';
    const RISKS                             = 'risks';
    const RISK_LOGS                         = 'risk_logs';
    const LISTS                             = 'lists';
    const LIST_ITEMS                        = 'list_items';
    const RISK_THRESHOLD_CONFIGS            = 'risk_threshold_configs';
    const MERCHANT_RISK_THRESHOLDS          = 'merchant_risk_thresholds';
    const REQUEST_TIMEOUT_INTERNATIONAL     = 10;
    const REQUEST_TIMEOUT_OTHERS            = 2;
    const REQUEST_TIMEOUT_ADMIN             = 45;
    const X_RAZORPAY_TASKID_HEADER          = 'X-Razorpay-TaskId';
    const X_REQUEST_ID                      = 'X-Request-ID';
    const X_RZP_TESTCASE_ID                 = 'X-RZP-TESTCASE-ID';
    const SERVER_ERROR_SHIELD_REQUEST_FAILED = "SERVER_ERROR_SHIELD_REQUEST_FAILED";

    const TRACE_REQUEST_FEATURE    = 'shield_dns_trace';

    protected $config;
    protected $baseUrlDom;
    protected $baseUrlIntl;
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

        $this->app = $app;

        $this->config = $app['config']->get('applications.shield');

        $this->trace = $app['trace'];

        $this->baseUrlDom = $this->config['url'];

        $this->baseUrlIntl = $this->config['url_international'];
    }

    public function fetchMultiple(string $entity, array $input)
    {
        $merchantId = $input['merchant_id'] ?? Account::SHARED_ACCOUNT;

        switch ($entity)
        {
            case self::RULES:
                return $this->getRules($input, $merchantId);

            case self::RULE_ANALYTICS:
                return $this->getRuleAnalytics($input);

            case self::RISKS:
                return $this->getRisks($input, $merchantId);

            case self::RISK_LOGS:
                return $this->getRiskLogs($input);

            case self::LISTS:
                return $this->getLists($input, $merchantId);

            case self::LIST_ITEMS:
                return $this->getListItems($input, $merchantId);

            case self::RISK_THRESHOLD_CONFIGS:
                return $this->getRiskThresholdConfigs($input);

            case self::MERCHANT_RISK_THRESHOLDS:
                return $this->getMerchantRiskThresholds($input);
        }

        return [];
    }

    public function fetch(string $entity, string $id, array $input)
    {
        $merchantId = $input['merchant_id'] ?? Account::SHARED_ACCOUNT;

        switch ($entity)
        {
            case self::RULES:
                return $this->getRuleById($id, $merchantId);

            case self::RISKS:
                return $this->getRiskById($id, $merchantId);

            case self::RISK_LOGS:
                return $this->getRiskLogById($id);

            case self::LISTS:
                return $this->getListById($id, $merchantId);

            case self::LIST_ITEMS:
                return $this->getListItemsById($id, $merchantId, $input);

            case self::RISK_THRESHOLD_CONFIGS:
                return $this->getRiskThresholdConfigById($id);

            case self::MERCHANT_RISK_THRESHOLDS:
                return $this->getMerchantRiskThresholdById($id);
        }

        return [];
    }

    public function createRule(array $input)
    {
        return $this->sendRequest($this->getMerchantPath(self::RULES_PATH), Requests::POST, $input);
    }

    public function getRules(array $input, string $merchantId)
    {
        return $this->sendRequest($this->getMerchantPath(self::RULES_PATH, $merchantId), Requests::GET, $input);
    }

    public function getRuleById(string $id, string $merchantId): array
    {
        return $this->sendRequest($this->getMerchantPath(self::RULES_PATH, $merchantId) . '/' . $id, Requests::GET);
    }

    public function deleteRuleById(string $id): array
    {
        return $this->sendRequest($this->getMerchantPath(self::RULES_PATH) . '/' . $id, Requests::DELETE);
    }

    public function updateRuleById(string $id, array $input): array
    {
        return $this->sendRequest($this->getMerchantPath(self::RULES_PATH) . '/' . $id, Requests::PUT, $input);
    }

    public function evaluateRules(array $input, bool $asyncCall = false): array
    {
        $path = self::EVALUATE_PATH;

        if ($asyncCall === true)
        {
            $path = $path . '?caller_type=async';
        }

        return $this->sendRequest($path, Requests::POST, $input);
    }

    public function runFraudCheck(Payment\Entity $payment): array
    {
        $paymentRequest = $this->getPaymentProperties($payment);

        return $this->evaluateRules($paymentRequest, true);
    }

    public function getRuleAnalytics(array $input): array
    {
        return $this->sendRequest(self::ANALYTICS_PATH, Requests::GET, $input);
    }

    public function getRisks(array $input, string $merchantId)
    {
        return $this->sendRequest($this->getMerchantPath(self::RISKS_PATH, $merchantId), Requests::GET, $input);
    }

    public function getRiskById(string $id, string $merchantId): array
    {
        return $this->sendRequest($this->getMerchantPath(self::RISKS_PATH, $merchantId) . '/' . $id, Requests::GET);
    }

    public function getRiskLogs(array $input)
    {
        return $this->sendRequest(self::RISK_LOGS_PATH, Requests::GET, $input);
    }

    public function getRiskLogById(string $id): array
    {
        return $this->sendRequest(self::RISK_LOGS_PATH . '/' . $id, Requests::GET);
    }

    public function getLists(array $input, string $merchantId): array
    {
        return $this->sendRequest($this->getMerchantPath(self::LISTS_PATH, $merchantId), Requests::GET, $input);
    }

    public function getListById(string $id, string $merchantId): array
    {
        return $this->sendRequest($this->getMerchantPath(self::LISTS_PATH, $merchantId) . '/' . $id, Requests::GET);
    }

    public function getListItems(array $input, $merchantId): array
    {
        $listItemPath = $this->getMerchantPath(self::LIST_ITEMS_PATH, $merchantId);

        $listId =  $input['list_id'] ?? 1; // by default use the first list

        unset($input['list_id']);

        $listItemPath = str_replace('{list_id}', $listId, $listItemPath);

        return $this->sendRequest($listItemPath, Requests::GET, $input);
    }

    public function getListItemsById(string $id, string $merchantId, array $input): array
    {
        $listItemPath = $this->getMerchantPath(self::LIST_ITEMS_PATH, $merchantId);

        $listId =  $input['list_id'] ?? 1; // by default use the first list

        $listItemPath = str_replace('{list_id}', $listId, $listItemPath);

        return $this->sendRequest($listItemPath . '/' . $id, Requests::GET);
    }

    public function getRiskThresholdConfigs(array $input)
    {
        return $this->sendRequest(self::RISK_THRESHOLD_CONFIGS_PATH, Requests::GET, $input);
    }

    public function getRiskThresholdConfigById(string $id): array
    {
        return $this->sendRequest(self::RISK_THRESHOLD_CONFIGS_PATH . '/' . $id, Requests::GET);
    }

    public function getMerchantRiskThresholds(array $input)
    {
        return $this->sendRequest(self::MERCHANT_RISK_THRESHOLDS_PATH, Requests::GET, $input);
    }

    public function getMerchantRiskThresholdById(string $id): array
    {
        return $this->sendRequest(self::MERCHANT_RISK_THRESHOLDS_PATH . '/' . $id, Requests::GET);
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
            'website'                     => $payment->merchant->merchantDetail->getWebsite()
        ];

        $methodParams = $this->fillMethodSpecificDetails($payment);

        $input = array_merge($input, $methodParams);

        if ($payment->hasOrder() === true)
        {
            $input['attempts'] = $payment->order->getAttempts();
        }

        //Pass client metadata to shield
        //These include browser fingerprint, local timezone etc
        if ($payment->hasMetadata('shield'))
        {
            $input['client_metadata'] = $payment->getMetadata('shield');
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

                $methodParams['card_iin']          = $card->getIin();
                $methodParams['card_network']      = $card->getNetwork();
                $methodParams['card_type']         = $card->getType();
                $methodParams['card_country']      = $card->getCountry();
                $methodParams['card_issuer']       = $card->getIssuer();
                $methodParams['card_name']         = $card->getName();
                $methodParams['card_last4']        = $card->getLast4();
                $methodParams['card_length']       = $card->getLength();
                $methodParams['card_expiry_month'] = $card->getExpiryMonth();
                $methodParams['card_expiry_year']  = $card->getExpiryYear();
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
            'auth'    => $this->getAuthHeaders(),
            'timeout' => $this->getTimeout($data),
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

        $url = $this->getBaseUrl($data, $path) . $path;

        if (isset($this->app['rzp.mode']) and $this->app['rzp.mode'] === 'test')
        {
            $testCaseId = $this->app['request']->header('X-RZP-TESTCASE-ID');

            if (empty($testCaseId) === false and strpos($testCaseId, 'TC_CARD_SH') !== false)
            {
                $headers[self::X_RZP_TESTCASE_ID] = $testCaseId;
                $url = $this->config['mock_url'] . $path;
            }
        }

        try
        {
            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            return $this->parseAndReturnResponse($response, $data);
        }
        catch(\WpOrg\Requests\Exception $e)
        {
            $data = [
                'exception'     => $e->getMessage(),
                'url'           => $url,
                'input'         => $data,
            ];

            $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR, $data);

            $this->trace->count(Payment\Metric::SHIELD_INTEGRATION_ERROR);

            throw $e;
        }
    }

    protected function parseAndReturnResponse($res, array $data)
    {
        $code = $res->status_code;

        $responseArray = json_decode($res->body, true);

        if ($code !== 200)
        {
            $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR,
                    [
                        'response' => $responseArray,
                        'request'  => $data,
                        'status'   => $code,
                    ]);

            if ((isset($responseArray[ShieldConstants::ACTION_KEY]) === false) or
                (in_array($responseArray[ShieldConstants::ACTION_KEY], ShieldConstants::ALLOWED_ACTIONS) === false))
            {
                throw new IntegrationException(ErrorCode::SERVER_ERROR_SHIELD_FRAUD_DETECTION_FAILED,
                    ErrorCode::SERVER_ERROR_SHIELD_FRAUD_DETECTION_FAILED);
            }
        }

        // In case json_decode fails, we $responseArray would be null.
        // We need to make sure that the response is always an array type
        if ($responseArray == null)
        {
            $responseArray = [];
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
            self::CONTENT_TYPE             => 'application/json',
            self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            self::X_REQUEST_ID             => $this->app['request']->getId(),
        ];
    }

    private function getMerchantPath(string $path, string $merchantId = Account::SHARED_ACCOUNT): string
    {
        return str_replace('{merchant_id}', $merchantId, $path);
    }

    private function getTimeout($input)
    {
        if (((isset($input['input'][Payment\Entity::INTERNATIONAL])) and
            ($input['input'][Payment\Entity::INTERNATIONAL] === true)) or
            ((isset($input['input']['card_network'])) and ($input['input']['card_network'] === Network::AMEX)))
        {
            return self::REQUEST_TIMEOUT_INTERNATIONAL;
        }

        return self::REQUEST_TIMEOUT_OTHERS;
    }

    private function getBaseUrl($input, $path)
    {
        if ($path === self::EVALUATE_PATH and
            ((isset($input['input'][Payment\Entity::INTERNATIONAL])) and
                ($input['input'][Payment\Entity::INTERNATIONAL] === true)))
        {
            $variant = $this->app['razorx']->getTreatment(UniqueIdEntity::generateUniqueId(), RazorxTreatment::SHIELD_INTL_POD, $this->app['basicauth']->getMode() ?? Mode::LIVE);

            if ($variant === RazorxTreatment::RAZORX_VARIANT_ON)
            {
                return $this->baseUrlIntl;
            }
        }

        return $this->baseUrlDom;
    }

    public function sendRequestV2(string $path, string $method, array $data = []): array
    {
        $url = $this->getBaseUrl($data, $path) . $path;

        $headers = $this->getShieldHeaders();

        $options = [
            'auth'    => $this->getAuthHeaders(),
            'timeout' => self::REQUEST_TIMEOUT_ADMIN,
        ];

        try
        {
            $content = null;

            if (in_array($method, [Requests::POST, Requests::PUT]) === true)
            {
                $content = '';
                if (empty($data) === false)
                {
                    $content = json_encode($data, JSON_UNESCAPED_SLASHES);
                }
            }

            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            return $this->formatResponse($response);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $data = [
                'exception'     => $e->getMessage(),
                'url'           => $url,
                'method'        => $method,
                'input'         => $data,
            ];

            $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR, $data);

            throw $e;
        }
    }

    //this function is exact copy of sendRequestV2 but it throws error if shield response status-code is not 200
    public function sendRequestV2ForWorkflow(string $path, string $method, array $data = []): array
    {
        $url = $this->getBaseUrl($data, $path) . $path;

        $headers = $this->getShieldHeaders();

        $options = [
            'auth'    => $this->getAuthHeaders(),
            'timeout' => self::REQUEST_TIMEOUT_ADMIN,
        ];

        try
        {
            $content = null;

            if (in_array($method, [Requests::POST, Requests::PUT]) === true)
            {
                $content = '';
                if (empty($data) === false)
                {
                    $content = json_encode($data, JSON_UNESCAPED_SLASHES);
                }
            }

            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            $formattedResponse = $this->formatResponse($response);

            if ($response->status_code !== 200)
            {
                $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR,
                    [
                        'response' => $formattedResponse,
                        'request'  => $data,
                        'status'   => $response->status_code,
                    ]);

                throw new IntegrationException(self::SERVER_ERROR_SHIELD_REQUEST_FAILED);
            }

            return $formattedResponse;
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $data = [
                'exception'     => $e->getMessage(),
                'url'           => $url,
                'method'        => $method,
                'input'         => $data,
            ];

            $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR, $data);

            throw $e;
        }
    }

    protected function formatResponse($response)
    {
        $responseArray = [];

        $responseBody = $response->body;

        if (empty($responseBody) === false)
        {
            $responseArray = json_decode($responseBody, true);
        }

        $formattedResponse = [
            'status_code' => $response->status_code,
        ];

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            $formattedResponse['error'] = 'invalid json, error code - ' . json_last_error();
            $formattedResponse['body'] = $responseBody;
        }
        else
        {
            $formattedResponse['body'] = $responseArray;
        }

        return $responseArray;
    }
}
