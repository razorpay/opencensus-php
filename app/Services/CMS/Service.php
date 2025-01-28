<?php

namespace RZP\Services\CMS;

use Request;
use Razorpay\Trace\Facades\Trace as TraceFacade;
use RZP\Constants\Metric;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;

class Service {
    const REQUEST_TIMEOUT = 5;
    const CMS_ROUTES = [
        'create_customer' => 'v2/internal/customers',
        'get_customer_by_reference_id' => 'v2/internal/customers/by/reference/%s',
        'update_customer_by_reference_id' => 'v2/internal/customers/by/reference/%s'
    ];

    protected $app;

    protected $baseUrl;

    protected $testModeBaseUrl;

    protected $config;

    protected $key;

    protected $secret;

    protected $testModeKey;

    protected $testModeSecret;
    private $trace;

    public function __construct($app)
    {
        $this->app = $app;
        $this->trace = TraceFacade::getFacadeRoot();

        $this->config = $app['config']->get('applications.cms');

        $this->baseUrl = $this->config['live_url'];
        $this->key     = $this->config['live_username'];
        $this->secret  = $this->config['live_password'];

        $this->testModeBaseUrl = $this->config['test_url'];
        $this->testModeKey     = $this->config['test_username'];
        $this->testModeSecret  = $this->config['test_password'];
    }

    /**
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function createCustomerV2($input,$merchantId)
    {
        $input = $this->transformV1CreateOptionsToV2CreateOptions($input,$merchantId);

        return $this->sendRequest(self::CMS_ROUTES['create_customer'], 'post', $input);
    }

    public function getCustomerByReferenceId($customerId)
    {
        return $this->sendRequest(sprintf(self::CMS_ROUTES['get_customer_by_reference_id'], $customerId), 'get');
    }

    public function updateCustomerByReferenceId($customerId, $payload)
    {
        return $this->sendRequest(sprintf(self::CMS_ROUTES['update_customer_by_reference_id'], $customerId), 'patch', $payload);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $inputData
     * @param array $headers
     *
     * @return array|mixed
     * @throws BadRequestException
     * @throws ServerErrorException
     */

    public function sendRequest($url, $method, array $inputData = [])
    {
        $baseUrl = $this->baseUrl;
        $key = $this->key;
        $secret = $this->secret;
        $success = true;

        if($this->app['rzp.mode'] === Mode::TEST){
            $baseUrl = $this->testModeBaseUrl;
            $key = $this->testModeKey;
            $secret = $this->testModeSecret;
        }

        $url = $baseUrl . $url;

        $data = '';

        if (empty($inputData) === false)
        {
            $data = json_encode($inputData);
        }

        $headers = [];

        $headers['Content-Type'] = 'application/json';

        // propagate request ID to CMS for tracing logs across applications
        $headers['X-Razorpay-Request-Id'] = $this->app['request']->header(RequestHeader::X_RAZORPAY_REQUEST_ID) ?? Request::getTaskId();

        $headers['Accept'] = 'application/json';

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$key, $secret],
        );

        $request = array(
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data,
        );

        $response = $this->sendCMSRequest($request,$success);

        if ($success) {
            if ($response->status_code !== 200) {
                $traceData = [
                    'body' => $response->body,
                    'status_code' => $response->status_code
                ];
                $this->trace->error(TraceCode::CMS_REQUEST_ERROR, $traceData);
            }
            else
            {
                $decodedResponse = json_decode($response->body, true);

                $decodedResponse = $decodedResponse ?? [];

                //check if $response is a valid json
                if (json_last_error() !== JSON_ERROR_NONE)
                {
                    $this->trace->error(TraceCode::CMS_INVALID_JSON_RESPONSE,
                        [
                            'body' => $response->body,
                            'status_code' => $response->status_code
                        ]);
                }
                else
                    return  $decodedResponse;
            }
        }

        throw new Exception\ServerErrorException("Request Failed to CMS",ErrorCode::SERVER_ERROR_INVALID_RESPONSE,[]);
    }

    /**
     * @param $request
     *
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    protected function sendCMSRequest($request, &$success)
    {
        $method = $request['method'];
        $response = null;
        $requestStartAt = microtime(true);

        try
        {
            if ($method == 'post' or $method == 'put' or $method == 'patch')
            {
                $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    $request['content'],
                    $request['options']);
            }
            else
            {
                $response = Requests::get(
                    $request['url'],
                    $request['headers'],
                    $request['options']);
            }
        }

        catch(\WpOrg\Requests\Exception $e)
        {
            $success = false;

            $this->trace->error(TraceCode::CMS_REQUEST_FAILED,[
                'error_message' => $e->getMessage()
            ]);
        }

        $this->trace->histogram(Metric::CMS_REQUEST_DURATION_MS, microtime(true) - $requestStartAt,
            [
                Metric::LABEL_ROUTE => $request['url'],
                Metric::LABEL_IS_SUCCESS => $success,
                Metric::LABEL_STATUS_CODE => $success ? $response->status_code: "",
            ]
        );

        return $response;
    }

    public function transformV1CreateOptionsToV2CreateOptions($opt, $merchantId)
    {
        $v2CreateOptions = [
            'salutation'          =>        null,
            'first_name'          =>        $opt['name'] ?? null,
            'middle_name'         =>        null,
            'last_name'           =>        null,
            'email'               =>        $opt['email'] ?? null,
            'contact'             =>        $opt['contact'] ? (string)$opt['contact'] : null,
            'notes'               =>        (object)$opt["notes"] ?? [],
            'gender'              =>        null,
            'dob'                 =>        null,
            'custom_data'         =>        (object)([]),
            'tax_details'         =>        $this->convertGstinToTaxDetails($opt['gstin'] ?? null),
            'merchant_id'         =>        $merchantId,
        ];

        return $v2CreateOptions;
    }

    // Helper function to convert Gstin to TaxDetails
    public function convertGstinToTaxDetails($gstin)
    {
        if (empty($gstin)) {
            return null;
        }

        return [
            [
            'type' => 'IN_GST',
            'value' => $gstin
            ]
        ];
    }
}
