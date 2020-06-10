<?php
namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Mail\Los\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use RZP\Http\Request\Requests as RzpRequest;

class LOSController extends Controller
{
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    const DISBURSE_LOAN_REGEX = 'DISBURSE_LOAN_REGEX';
    const GET_OFFLINE_VERIFICATION_TASKS_REGEX = 'GET_OFFLINE_VERIFICATION_TASKS_REGEX';
    const CREATE_VERIFICATION_REGEX = 'CREATE_VERIFICATION_REGEX';
    const SCHEDULE_VERIFICATION_REGEX = 'SCHEDULE_VERIFICATION_REGEX';
    const GET_SEED_INFO_REGEX = 'GET_SEED_INFO_REGEX';
    const LEEGALITY_WEBHOOK_URL = 'twirp/rzp.capital.los.contracts.v1.DocSignAPI/LeegalityWebhook';

    const WORKFLOW_REGEX_ROUTES = [
        self::DISBURSE_LOAN_REGEX => '/capital\.los\.admin\.v1\.DisbursalAPI\/CreateDisbursal/',
    ];

    const MERCHANT_ROUTES_REGEX = [
        self::GET_OFFLINE_VERIFICATION_TASKS_REGEX => '/rzp\.capital\.los\.admin\.v1\.OfferVerificationAPI\/GetOfferVerificationTasks/',
        self::CREATE_VERIFICATION_REGEX            => '/rzp\.capital\.los\.admin\.v1\.OfferVerificationAPI\/CreateVerification/',
        self::SCHEDULE_VERIFICATION_REGEX          => '/rzp\.capital\.los\.admin\.v1\.OfferVerificationAPI\/ScheduleVerification/',
        self::GET_SEED_INFO_REGEX                  => '/rzp\.capital\.los\.origination\.v1\.ApplicationAPI\/GetSeedInfo/'
    ];

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body   = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
            'body'    => $body,
        ]);

        foreach (self::MERCHANT_ROUTES_REGEX as $route => $regex)
        {
            if (preg_match($regex, $path, $matches) === 0)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
            }
        }

        $headers = [
            'X-Merchant-Id'    => $this->ba->getMerchant()->getId() ?? '',
            'X-Merchant-Email' => $this->ba->getMerchant()->getEmail() ?? '',
            'X-Auth-Type'      => 'proxy',
        ];

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);
        return $response;
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body   = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
            'body'    => $body,
        ]);

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        //foreach (self::WORKFLOW_REGEX_ROUTES as $route => $regex)
        //{
        //    if (preg_match($regex, $path, $matches) === 1)
        //    {
        //        switch ($route)
        //        {
        //            case self::DISBURSE_LOAN_REGEX:
        //                $this->startWorkflow($body);
        //                break;
        //            default:
        //                break;
        //        }
        //        break;
        //    }
        //}

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);
        return $response;
    }

    protected function handleLeegalityWebhook($path = null)
    {
        $request = Request::instance();
        $url = self::LEEGALITY_WEBHOOK_URL;
        $body   = $request->all();

        $headers = [
            'X-Service-Name'    => $this->ba->getInternalApp() ?? '',
            'X-Auth-Type'       => 'internal',
        ];

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
            'body'    => $body,
        ]);

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);
        return $response;
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $config = config('applications.loan_origination_system');
        $baseUrl = $config['url'];
        $username = $config['username'];
        $password = $config['secret'];
        $timeout = $config['timeout'];
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id'] = $this->app['request']->getTaskId();

        $auth = [$username, $password];
        $defaultOptions = [
            'timeout' => $timeout,
            'auth'    => $auth,
        ];
        $method = self::POST;

        try
        {
            $response = RzpRequest::request(
                $baseUrl . $url,
                $headers,
                empty($body) ? "{}" : json_encode($body),
                $method,
                $defaultOptions
            );
        }
        catch (\Requests_Exception $e)
        {
            $errorCode = ($this->hasRequestTimedOut($e) === true) ?
                ErrorCode::GATEWAY_ERROR_LOAN_ORIGINATION_SYSTEM_TIMEOUT :
                ErrorCode::GATEWAY_ERROR_LOAN_ORIGINATION_SYSTEM_FAILURE;
            throw new Exception\IntegrationException(
                $e->getMessage(),
                $errorCode,
                null,
                $e
            );
        }
        return $this->parseResponse($response);
    }

    protected function hasRequestTimedOut(\Requests_Exception $e): bool
    {
        $message = $e->getMessage();
        return Str::contains($message, [
            'operation timed out',
            'network is unreachable',
            'name or service not known',
            'failed to connect',
            'could not resolve host',
            'resolving timed out',
            'name lookup timed out',
            'connection timed out',
            'aborted due to timeout',
        ]);
    }

    protected function parseResponse($response)
    {
        $code = $response->status_code;
        $body = json_decode($response->body, true);

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_RESPONSE, [
            'status_code' => $code,
            'body' => $body,
        ]);

        if (isset($body['code']) === true)
        {
            throw new Exception\TwirpException($body);
        }
        elseif ($response->status_code === 404)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        return ApiResponse::json($body, $code);
    }

    protected function sendMail()
    {
        $request = Request::instance();
        $data   = $request->all();
        if ((isset($data['name']) === false) or
            (isset($data['email']) === false) or
            (isset($data['fields']) === false) or
            (isset($data['subject']) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }

        $mail = new Base($data);
        Mail::queue($mail);

        return ApiResponse::json(['success' => true]);
    }

    protected function startWorkflow($body)
    {
        $this->app['workflow']
            ->setEntityAndId('loan_origination_system', $body['disbursal']['application_id'])
            ->handle([], ['status' => 'loan_origination_system_workflow_started']);
    }
}
