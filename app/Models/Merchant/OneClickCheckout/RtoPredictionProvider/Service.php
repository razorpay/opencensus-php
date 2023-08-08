<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoPredictionProvider;

use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use RZP\Http\Request\Requests;
use RZP\Models\Order;

class Service
{
    protected $app;

    const COD_ELIGIBILITY_EVALUATE              = 'cod_eligibility_api';
    const BULK_UPSERT_COD_ELIGIBILITY_ATTRIBUTE = 'bulk_upsert_cod_eligibility_attribute';
    const LIST_COD_ELIGIBILITY_ATTRIBUTE        = "list_cod_eligibility_attribute";
    const DELETE_COD_ELIGIBILITY_ATTRIBUTE      = "delete_cod_eligibility_attribute";
    const DELETE_BY_COD_ELIGIBILITY_ATTRIBUTE   = "delete_by_cod_eligibility_attribute";
    const PATH                                  = 'path';

    const COD_ELIGIBILITY_ATTRIBUTES = "cod_eligibility_attributes";
    const ATTRIBUTE_TYPE             = "attribute_type";
    const ATTRIBUTE_VALUE            = "attribute_value";
    const COD_ELIGIBILITY_TYPE       = "cod_eligibility_type";
    const CREATED_BY                 = "created_by";
    const MERCHANT_ID                = "merchant_id";

    const GET_MERCHANT_ORDER_REVIEW_AUTOMATION_RULES    = "get_merchant_order_review_automation_rules";
    const UPSERT_MERCHANT_ORDER_REVIEW_AUTOMATION_RULES = "upsert_merchant_order_review_automation_rules";
    const CREATE_JOB_EXECUTIONS = 'create_job_executions_api';

    const COD_ELIGIBILITY_CACHE_KEY_PREFIX = 'TW_ADDRESS_COD_ELIGIBILITY:';
    const COD_ELIGIBILITY_CACHE_VALIDITY   = 1800; // 30 minutes


    const PARAMS = [
        self::COD_ELIGIBILITY_EVALUATE  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.cod_eligibility.v1.CODEligibilityAPI/Evaluate',
        ],
        self::BULK_UPSERT_COD_ELIGIBILITY_ATTRIBUTE => [
            self::PATH => 'twirp/rzp.rto_prediction.cod_eligibility_attribute.v1.CODEligibilityAttributeAPI/BulkUpsertCODEligibilityAttribute',
        ],
        self::LIST_COD_ELIGIBILITY_ATTRIBUTE => [
            self::PATH => 'twirp/rzp.rto_prediction.cod_eligibility_attribute.v1.CODEligibilityAttributeAPI/ListCODEligibilityAttribute',
        ],
        self::DELETE_COD_ELIGIBILITY_ATTRIBUTE => [
            self::PATH => 'twirp/rzp.rto_prediction.cod_eligibility_attribute.v1.CODEligibilityAttributeAPI/DeleteCODEligibilityAttribute',
        ],
        self::DELETE_BY_COD_ELIGIBILITY_ATTRIBUTE => [
            self::PATH => 'twirp/rzp.rto_prediction.cod_eligibility_attribute.v1.CODEligibilityAttributeAPI/DeleteByCODEligibilityAttribute',
        ],
        self::GET_MERCHANT_ORDER_REVIEW_AUTOMATION_RULES => [
            self::PATH => 'twirp/rzp.rto_prediction.merchant_order_review_automation.v1.MerchantOrderReviewAutomationAPI/GetRuleConfigs',
        ],
        self::UPSERT_MERCHANT_ORDER_REVIEW_AUTOMATION_RULES => [
            self::PATH => 'twirp/rzp.rto_prediction.merchant_order_review_automation.v1.MerchantOrderReviewAutomationAPI/UpsertRuleConfigs',
        ],
        self::CREATE_JOB_EXECUTIONS => [
            self::PATH => '/twirp/rzp.rto_prediction.job_executions.v1.JobExecutionApi/Create',
        ]
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function evaluate($input)
    {
        $input = $this->addOrderDetails($input);

        $cacheResponse = $this->getCodEligibilityFromCache($input);

        if (empty($cacheResponse) === false)
        {
            $this->app['trace']->info(TraceCode::COD_ELIGIBILITY_VALIDITY_CACHE_RESPONSE, ['cached_response' => $cacheResponse]);
            return $cacheResponse;
        }

        $params = self::PARAMS[self::COD_ELIGIBILITY_EVALUATE];

        $response = $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);

        $this->cacheCodEligibility($input, $response);

        return $response;
    }

    private function addOrderDetails(array $input) : array
    {
        $orderId = $input['order_id'];

        $address = $input['address'];

        $orderDetails = (new Order\Service())->fetchByIdInternal($orderId);

        $uniqueId = $orderId . ':' . Str::uuid();

        $rtoServiceRequestContent['id'] = $uniqueId;

        $rtoServiceRequestContent['merchant_id'] = app('basicauth')->getMerchantId();

        $order['id'] = $orderId;

        $order['checkout_id'] = $uniqueId;

        $order['amount'] = $orderDetails['amount'] ?? 0;

        $order['currency'] = $orderDetails['currency'] ?? "";

        $order['created_at'] = $orderDetails['created_at'] ?? 0;

        $order['shipping_address'] = $orderDetails['customer_details']['shipping_address'] ?? [];

        $order['billing_address'] = $orderDetails['customer_details']['billing_address'] ?? [];

        $order['customer']['id'] = $address['contact'] ?? "";

        $order['customer']['phone'] = $address['contact'] ?? "";

        $order['customer']['email'] = $orderDetails['customer_details']['email'] ?? "";

        $order['device'] = $input['device'] ?? [];

        $order['device']['pathname'] = $_SERVER['REQUEST_URI'] ?? "";

        $order['device']['search'] = $_SERVER['QUERY_STRING'] ?? "";

        if (strcmp($address['type'], 'shipping_address') == 0 )
        {
            $order['shipping_address']['id'] = $address['id'] ?? "";

            $order['shipping_address']['line1'] = $address['line1'] ?? "";

            $order['shipping_address']['line2'] = $address['line2'] ?? "";

            $order['shipping_address']['zipcode'] = $address['zipcode'] ?? "";

            $order['shipping_address']['city'] = $address['city'] ?? "";

            $order['shipping_address']['state'] = $address['state'] ?? "";

            $order['shipping_address']['tag'] = $address['tag'] ?? "";

            $order['shipping_address']['country'] = $address['country'] ?? "";

            $order['shipping_address']['name'] = $address['name'] ?? "";
        }

        unset($order['shipping_address']['type']);

        unset($order['shipping_address']['landmark']);

        unset($order['shipping_address']['contact']);

        unset($order['billing_address']['type']);

        unset($order['billing_address']['landmark']);

        unset($order['billing_address']['contact']);

        $rtoServiceRequestContent['input']['order'] = $order;

        return $rtoServiceRequestContent;
    }

    public function bulkUpsert($input, $merchantId, $userEmail, $codEligibilityType)
    {
        $input = $this->addCreatedByAndMerchantID($input, $merchantId, $userEmail, $codEligibilityType);

        $params = self::PARAMS[self::BULK_UPSERT_COD_ELIGIBILITY_ATTRIBUTE];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    private function addCreatedByAndMerchantID($input, $merchantId, $userEmail, $codEligibilityType) : array
    {
        $codEligibilityAttributes = array();

        foreach ($input[self::COD_ELIGIBILITY_ATTRIBUTES] as $codEligibilityAttribute)
        {
            $codEligibilityAttribute[self::COD_ELIGIBILITY_TYPE] = $codEligibilityType;

            $codEligibilityAttribute[self::CREATED_BY] = $userEmail;

            $codEligibilityAttribute[self::MERCHANT_ID] = $merchantId;

            $codEligibilityAttributes[] = $codEligibilityAttribute;
        }

        $bulkRequestContent[self::COD_ELIGIBILITY_ATTRIBUTES] = $codEligibilityAttributes;

        return $bulkRequestContent;
    }

    public function batchUpsert($input, $merchantId, $userEmail, $codEligibilityType)
    {
        $input = $this->addCreatedByAndMerchantIDForBatch($input, $merchantId, $userEmail, $codEligibilityType);

        $params = self::PARAMS[self::BULK_UPSERT_COD_ELIGIBILITY_ATTRIBUTE];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    private function addCreatedByAndMerchantIDForBatch($input, $merchantId, $userEmail, $codEligibilityType) : array
    {
        $codEligibilityAttributes = array();

        foreach ($input as $codEligibilityAttribute)
        {
            $codEligibilityAttribute[self::COD_ELIGIBILITY_TYPE] = $codEligibilityType;

            $codEligibilityAttribute[self::CREATED_BY] = $userEmail;

            $codEligibilityAttribute[self::MERCHANT_ID] = $merchantId;

            unset($codEligibilityAttribute['idempotent_id']);

            $codEligibilityAttributes[] = $codEligibilityAttribute;
        }

        $bulkRequestContent[self::COD_ELIGIBILITY_ATTRIBUTES] = $codEligibilityAttributes;

        return $bulkRequestContent;
    }

    public function list($input, $merchantId, $codEligibilityType)
    {
        $input[self::MERCHANT_ID] = $merchantId;

        $input[self::COD_ELIGIBILITY_TYPE] = $codEligibilityType;

        $params = self::PARAMS[self::LIST_COD_ELIGIBILITY_ATTRIBUTE];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function delete($id, $merchantId)
    {
        $input[self::MERCHANT_ID] = $merchantId;

        $input['id'] = $id;

        $params = self::PARAMS[self::DELETE_COD_ELIGIBILITY_ATTRIBUTE];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function deleteByAttribute($merchantId, $codEligibilityType, $attributeType, $attributeValue)
    {
        $input[self::MERCHANT_ID] = $merchantId;

        $input[self::COD_ELIGIBILITY_TYPE] = $codEligibilityType;

        $input[self::ATTRIBUTE_TYPE] = $attributeType;

        $input[self::ATTRIBUTE_VALUE] = $attributeValue;

        $params = self::PARAMS[self::DELETE_BY_COD_ELIGIBILITY_ATTRIBUTE];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function getMerchantOrderReviewAutomationRuleConfigs($merchantId)
    {
        $input[self::MERCHANT_ID] = $merchantId;

        $params = self::PARAMS[self::GET_MERCHANT_ORDER_REVIEW_AUTOMATION_RULES];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function upsertMerchantOrderReviewAutomationRuleConfigs($input)
    {
        $params = self::PARAMS[self::UPSERT_MERCHANT_ORDER_REVIEW_AUTOMATION_RULES];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function createJobExecutions($input)
    {
        $params = self::PARAMS[self::CREATE_JOB_EXECUTIONS];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    private function cacheCodEligibility($input, $response)
    {
        try
        {
            $this->app['cache']->put(
                $this->getCodEligibilityCacheKey($input),
                $response,
                self::COD_ELIGIBILITY_CACHE_VALIDITY);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->error(TraceCode::COD_ELIGIBILTY_RTO_SERVICE_RESPONSE_CACHE_CREATE_FAILED,
                [
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'trace'   => $e->getTraceAsString(),
                ]
            );
        }
    }

    private function getCodEligibilityFromCache($input)
    {
        try
        {
            $cachedKey = $this->getCodEligibilityCacheKey($input);
            return $this->app['cache']->get($cachedKey);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->error(TraceCode::COD_ELIGIBILTY_RTO_SERVICE_RESPONSE_CACHE_FETCH_FAILED,
                [
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'trace'   => $e->getTraceAsString(),
                ]
            );
        }
    }

    private function getCodEligibilityCacheKey($data): string
    {
        $cachedParams = [];

        if (isset($data['input']) === true && isset($data['input']['order']) === true) {

            $order = $data['input']['order'];

            $cachedParams['order_id'] = $order['id'];

            if (isset($order['shipping_address']['id']) === true) {
                unset($order['shipping_address']['id']);
                $cachedParams['shipping_address'] = $order['shipping_address'];
            }

            if (isset($order['device']) === true) {
                if (isset($order['device']['pathname']) === true) {
                    unset($order['device']['pathname']);
                }

                if (isset($order['device']['search']) === true) {
                    unset($order['device']['search']);
                }

                $cachedParams['device'] = $order['device'];
            }

            if (isset($order['customer']) === true) {
                $cachedParams['customer'] = $order['customer'];
            }
        }

        return self::COD_ELIGIBILITY_CACHE_KEY_PREFIX . (md5(json_encode($cachedParams)));
    }

}
