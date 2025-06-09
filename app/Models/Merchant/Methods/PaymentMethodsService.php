<?php

namespace RZP\Models\Merchant\Methods;

use Illuminate\Support\Facades\App;
use RZP\Constants\Mode;
use Request;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\IntegrationException;
use RZP\Http\Request\Requests;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Methods\Entity as MethodsEntity;
use RZP\Trace\TraceCode;
use function PHPUnit\Framework\isNull;

class PaymentMethodsService
{

    protected $app;
    protected $auth;
    protected $config;
    protected $trace;
    protected $baseUrl;
    protected $request;

    public function __construct()
    {
        $app = App::getFacadeRoot();
        $this->app = $app;
        $this->trace = $this->app['trace'];
        $this->request = $app['request'];
        $this->auth = $this->app['basicauth'];
    }
    public function ProxyToMethodsService($input, $method, $path, $options = [], $headers = [])
    {
        $url = $this->getMethodsServiceUrl() . $path;
        $response = null;
        $logContext = [
            'url'     => $url,
            'method'  => $method,
            'path'    => $path,
            'service' => 'payment_methods',
        ];

        try
        {
            $defaultOptions = [
                'timeout'         => $this->getTimeout(), // Total timeout in seconds
                'connect_timeout' => $this->getTimeout(), // Connection timeout in seconds
            ];

            // Merge default options with any incoming options
            $requestOptions = array_merge($defaultOptions, $options);

            $response = $this->makeRequest($url, $headers, $input, $method, $requestOptions);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::EXTERNAL_REPO_REQUEST_FAILURE);

            $this->trace->count(Metric::PAYMENT_METHOD_SERVICE_CALL_FAILED_METRIC);

            throw new Exception\IntegrationException(
                'Network error communicating with Payment Methods Service: ' . $e->getMessage(),
                ErrorCode::SERVER_ERROR,
                null,
                $e
            );
        }

        $statusCode = $response->status_code;

        if ($statusCode >= 400)
        {
            $errorContext = $logContext + [
                'status_code' => $statusCode,
                'response_body' => $response->body,
            ];

            $this->trace->error(TraceCode::PAYMENT_METHODS_SERVICE_CALL_FAILED, $errorContext);

            $this->trace->count(Metric::PAYMENT_METHOD_SERVICE_CALL_FAILED_METRIC);


            throw new Exception\IntegrationException(
                'Payment Methods Service returned an error.',
                ErrorCode::SERVER_ERROR_INVALID_RESPONSE,
                [
                    'status_code' => $statusCode,
                    'response_body' => $response->body,
                ]
            );
        }

        return $response;
    }


    public function getMethodsServiceUrl()
    {
        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;
        $urlConfig = 'applications.payment_methods_service.' . $mode . '.url';

        return $this->app['config']->get($urlConfig);
    }

    protected function makeRequest($url, $headers, $content, $method, $options)
    {
        $headers = array_merge($headers, [
            'Authorization' => $this->getAuthHeader(),
            'Content-Type'  => 'application/json',
        ]);
        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function getAuthHeader(): string
    {
        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;
        $passwordConfig = 'applications.payment_methods_service.' . $mode . '.password';
        $userConfig = 'applications.payment_methods_service.' . $mode . '.user';
        $password = $this->app['config']->get($passwordConfig);
        $user = $this->app['config']->get($userConfig);
        return 'Basic ' . base64_encode($user . ':' . $password);
    }

    /**
     * Fetches methods from the payment_methods service and parses the response into an entity.
     *
     * @param string $merchantId The merchant ID.
     * @param string $path       The API path for the methods service
     * @return MethodsEntity|null The fetched Methods Entity.
     * @throws Exception\IntegrationException If the proxy call fails or returns an error.
     * @throws Exception\RuntimeException If JSON decoding fails.
     */
    public function fetchMethodsFromService(string $merchantId, string $path): ?MethodsEntity
    {
        $this->trace->info(TraceCode::PAYMENT_METHODS_SERVICE_FETCH_REQUEST, [
            'merchant_id' => $merchantId,
            'path'        => $path,
        ]);

        $requestStartAt = millitime();
        $response = $this->ProxyToMethodsService([], 'GET', $path);
        $this->trace->histogram(Metric::PAYMENT_METHODS_SERVICE_FETCH_LATENCY, millitime() - $requestStartAt);

        // Decode the JSON response
        $decodedData = json_decode($response->body, true);

        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
             $this->trace->error(TraceCode::PAYMENT_METHODS_SERVICE_FETCH_COMPARE_JSON_ERROR, [
                'merchant_id' => $merchantId,
                'path'        => $path,
                'error'       => json_last_error_msg(),
                'response_body' => $response->body, // Log raw response on error
            ]);
            $this->trace->count(Metric::PAYMENT_METHOD_SERVICE_CALL_FAILED_METRIC);
            throw new Exception\RuntimeException('Failed to decode JSON response from Payment Methods Service: ' . json_last_error_msg());
        }

        // Create a new MethodsEntity instance and fill it with the fetched data
        $fetchedEntity = new MethodsEntity();
        $fetchedEntity->forceFill($decodedData);

         $this->trace->info(TraceCode::PAYMENT_METHODS_SERVICE_FETCH_SUCCESS, [
            'merchant_id' => $merchantId,
            'fetched_data' => $fetchedEntity->toArrayAdmin(),
        ]);

        $this->trace->count(Metric::PAYMENT_METHOD_SERVICE_CALL_SUCCESS_METRIC);

        return $fetchedEntity;
    }

    /**
     * Helper method to format values for logging.
     * Encodes arrays/objects to JSON strings.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function formatValueForLog($value)
    {
        if (is_scalar($value) || is_null($value))
        {
            return $value;
        }
        // For arrays or objects, return their JSON representation.
        return json_encode($value);
    }

    /**
     * Compares two Methods Entities to check if their attributes are different.
     * Excludes timestamps and primary key from comparison.
     * Logs detailed differences if any are found.
     *
     * @param MethodsEntity $entity1
     * @param MethodsEntity $entity2
     * @return bool True if entities have different attribute values, false otherwise.
     */
    public function areMethodsDifferent(?MethodsEntity $entity1, ?MethodsEntity $entity2): bool
    {
        // Case 1: Both are null, so they are not different.
        if ($entity1 === null && $entity2 === null)
        {
            return false;
        }

        // Case 2: One is null and the other is not, so they are different.
        if ($entity1 === null || $entity2 === null)
        {
            return true;
        }

        // Case 3: Neither is null, proceed with attribute comparison.
        $array1 = $entity1->attributesToArray();
        $array2 = $entity2->attributesToArray();

        $areDifferent = false;
        $detailedDifferences = [];

        // Iterate through entity1 (source of truth)
        // Differences are:
        // 1. A key in entity1 is missing in entity2.
        // 2. A key is in both, but values differ.
        // Extra keys in entity2 are NOT considered a difference.
        // Timestamps are excluded from comparison.
        foreach ($array1 as $key => $value1)
        {
            // Skip timestamp fields
            if ($key === 'created_at' || $key === 'updated_at')
            {
                continue;
            }

            if (!array_key_exists($key, $array2))
            {
                $detailedDifferences[$key] = [
                    'expected_value' => $this->formatValueForLog($value1),
                    'status'         => 'MISSING_IN_ENTITY2',
                ];
                $areDifferent = true;
            }
            else
            {
                $value2 = $array2[$key];
                if ($value1 !== $value2)
                {
                    $detailedDifferences[$key] = [
                        'expected_value' => $this->formatValueForLog($value1),
                        'actual_value'   => $this->formatValueForLog($value2),
                    ];
                    $areDifferent = true;
                }
            }
        }

        $logPayload = ['are_different' => $areDifferent];

        if ($areDifferent && !empty($detailedDifferences))
        {
            // Sort by key for consistent log output
            ksort($detailedDifferences);
            $logPayload['differences'] = $detailedDifferences;
        }

         $this->trace->info(
             TraceCode::PAYMENT_METHODS_SERVICE_COMPARE_RESULT,
             $logPayload
         );

        return $areDifferent;
    }

    public function isMethodServiceReadEnabled(){
        $experiment = 'applications.payment_methods_service.read_experiment';

        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get($experiment),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === 'variant_on')
            {
                $this->trace->info(TraceCode::SPLITZ_EXPERIMENT_RESULT, [
                    'variant' => $variant,
                ]);
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR);
        }
        $this->trace->info(TraceCode::SPLITZ_EXPERIMENT_RESULT, [
            'variant' => $variant,
        ]);
        return false;
    }

    public function getTimeout(){
        $timeout = 'applications.payment_methods_service.timeout';
        return $this->app['config']->get($timeout);
    }

    /**
     * @throws \Throwable
     * @throws IntegrationException
     */
    public function saveMethods(MethodsEntity $entity, array $options = [])
    {
        try
        {
            $this->trace->info(TraceCode::PAYMENT_METHODS_SERVICE_SAVE_ATTEMPT, [
                'merchant_id' => $entity->getMerchantId(),
                'entity_id'   => $entity->getId(),
            ]);
            $this->trace->count(Metric::PAYMENT_METHOD_SERVICE_UPDATE_ATTEMPT);

            $path = '/v1/merchant/methods';
            $this->trace->info(TraceCode::DEBUG_LOGGING, [
                'request' => json_encode($entity),
            ]);
            $this->ProxyToMethodsService(json_encode($entity), 'POST', $path, $options);

            $this->trace->info(TraceCode::PAYMENT_METHODS_SERVICE_SAVE_SUCCESS, [
                'merchant_id' => $entity->getMerchantId(),
                'entity_id'   => $entity->getId(),
            ]);
            $this->trace->count(Metric::PAYMENT_METHOD_SERVICE_UPDATE_SUCCESS);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_METHODS_SERVICE_UPDATE_CALL_FAILED, [
                'merchant_id' => $entity->getMerchantId(),
                'entity_id'   => $entity->getId(),
            ]);
            $this->trace->count(Metric::PAYMENT_METHOD_SERVICE_UPDATE_FAILURE);
            throw $e;
        }
    }
    public function isMethodServiceWriteEnabled(): bool
    {
        $experiment = 'applications.payment_methods_service.write_experiment';
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get($experiment),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === 'variant_on')
            {
                $this->trace->info(TraceCode::SPLITZ_EXPERIMENT_RESULT, [
                    'variant' => $variant,
                ]);
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR);
        }
        $this->trace->info(TraceCode::SPLITZ_EXPERIMENT_RESULT, [
            'variant' => $variant,
        ]);
        return false;
    }

}
