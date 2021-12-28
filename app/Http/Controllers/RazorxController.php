<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Http\Request\Requests;
use ApiResponse;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use Razorpay\Trace\Logger as Trace;

class RazorxController extends Controller
{
    const REQUEST_TIMEOUT = 5;

    const CONTENT_TYPE_JSON = 'application/json';

    const ACTION_ADMIN_EMAIL_PARAM_NAME = 'action_admin_email';

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $key;

    /*
     * @var string
     */
    protected $secret;

    const READ_METHODS = [
        Requests::GET,
        Requests::HEAD,
    ];

    const EXPERIMENT_ACTIVATE_ROUTE = 'EXPERIMENT_ACTIVATE_ROUTE';

    const WORKFLOW_REGEX_ROUTES = [
        self::EXPERIMENT_ACTIVATE_ROUTE => '/^experiments\/(\w+)\/activate$/',
    ];

    public function __construct()
    {
        parent::__construct();

        $razorxConfig  = $this->config->get('applications.razorx');
        $this->baseUrl = $razorxConfig['url'];
        $this->key     = $razorxConfig['username'];
        $this->secret  = $razorxConfig['secret'];
    }

    public function sendRequest()
    {
        $this->addActionAdminEmail();

        $path           = $this->validateAndGetServicePathParam();
        $method         = null;
        $requestParams  = $this->getRequestParams();

        foreach (self::WORKFLOW_REGEX_ROUTES as $route => $regex)
        {
            if (preg_match($regex, $path, $matches) === 1)
            {
                switch ($route)
                {
                    case self::EXPERIMENT_ACTIVATE_ROUTE:
                        $experimentId = $matches[1];

                        $this->validateActivateRequest($experimentId, $requestParams);
                        $this->startWorkflow($experimentId);

                        $method = 'PATCH';
                        break;

                    default:
                        break;
                }
                break;
            }
        }

        if ($method !== null)
        {
            $requestParams['method'] = $method;
        }

        try
        {
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);

            $res = $this->parseAndReturnResponse($response);

            return ApiResponse::json($res);
        }
        catch(\Throwable $e)
        {
            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_RAZORX_FAILURE,
                null,
                $e
            );
        }
    }

    protected function startWorkflow($experimentId)
    {
        $this->app['workflow']
             ->setEntityAndId('razorx_experiment_activate', $experimentId)
             ->handle([], ['status' => 'razorx_experiment_workflow_started']);
    }

    protected function validateActivateRequest($experimentId, $requestParams)
    {
        $url    = $this->baseUrl . "validate/experiment/$experimentId/activate";
        $method = 'POST';

        $validateResponse = Requests::request(
            $url,
            [],
            $requestParams['data'],
            $method,
            $requestParams['options']
        );

        $razorxResponse = $this->parseAndReturnResponse($validateResponse);

        if ($razorxResponse['status_code'] !== 200)
        {
            throw new Exception\BadRequestValidationFailureException(
                $razorxResponse['response'],
                null,
                null
            );
        }

    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $res = json_decode($res->body, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'Malformed json response');
        }

        $razorxResponse = [
            'status_code' => $code,
            'response'    => $res,
        ];

        return $razorxResponse;
    }

    protected function getRequestParams()
    {
        $path = $this->validateAndGetServicePathParam();

        $url = $this->baseUrl . $path;

        $method = Request::method();

        $headers = [];
        $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);

        $parameters = [];

        if (in_array($method, self::READ_METHODS, true) === false)
        {
            $parameters = Request::all();

            unset($parameters['service_path']);

            $parameters = json_encode($parameters);

            $headers['content_type'] = self::CONTENT_TYPE_JSON;
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::RAZORX_REQUEST, ['url' => $url, 'parameters' => $parameters]);

        $response = [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $parameters,
            'options' => $options,
            'method'  => $method,
        ];

        return $response;
    }

    protected function validateAndGetServicePathParam(): string
    {
        $path = Request::get('service_path');

        if (empty($path) === true)
        {
            throw new Exception\BadRequestValidationFailureException('Valid path parameter required');
        }

        return $path;
    }

    protected function addActionAdminEmail()
    {
        $admin = app()['basicauth']->getAdmin();

        if ($admin === null)
        {
            throw new Exception\BadRequestValidationFailureException('admin auth not present.');
        }

        $adminEmail = $admin->getEmail();

        Request::merge([self::ACTION_ADMIN_EMAIL_PARAM_NAME => $adminEmail]);
    }

    public function getTreatment($id, $featureFlag)
    {
        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = app('razorx')->getTreatment($id, $featureFlag, $mode);

        $response = ['result' => $result];

        return $response;
    }
}
