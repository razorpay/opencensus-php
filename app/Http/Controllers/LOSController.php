<?php

namespace RZP\Http\Controllers;

use Config;
use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Mail\Los\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Services\LOSService;
use RZP\Exception\TwirpException;
use Illuminate\Support\Facades\Mail;
use RZP\Exception\IntegrationException;

class LOSController extends Controller
{
    const LEEGALITY_WEBHOOK_URL = 'twirp/rzp.capital.los.contracts.v1.DocSignAPI/LeegalityWebhook';

    /**
     * @var LOSService
     */
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = $this->app['losService'];
    }

    /**
     * @throws TwirpException
     * @throws IntegrationException
     */
    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Merchant-Id'    => $this->ba->getMerchant()->getId() ?? '',
            'X-Merchant-Email' => $this->ba->getMerchant()->getEmail() ?? '',
            'X-User-Id'        => $this->ba->getUser()->getId() ?? '',
            'X-User-Role'      => $this->ba->getUserRole() ?? '',
            'X-Auth-Type'      => 'proxy',
        ];

        $response = $this->service->sendRequest($url, $body, $headers);

        return $this->service->parseResponse($response);
    }

    /**
     * @throws TwirpException
     * @throws IntegrationException
     */
    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $rolesAndPermissionList = $this->service->getCapitalRolesAndPermissionsForAdmin();
        $headers                = [
            'X-Admin-Id'                  => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email'               => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'                 => 'admin',
            'X-Admin-Permissions'         => $rolesAndPermissionList['permissions'],
            'X-Admin-Roles'               => $rolesAndPermissionList['roles'],
            RequestHeader::ACCEPT_VERSION => $request->header(RequestHeader::ACCEPT_VERSION, ''),
        ];

        $response= $this->service->sendRequest($url, $body, $headers);

        return $this->service->parseResponse($response);
    }

    /**
     * @throws TwirpException
     * @throws IntegrationException
     */
    protected function handleDevAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $rolesAndPermissionList = $this->service->getCapitalRolesAndPermissionsForAdmin();
        $headers                = [
            'X-Admin-Id'          => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email'       => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'         => 'admin',
            'X-Admin-Permissions' => $rolesAndPermissionList['permissions'],
            'X-Admin-Roles'       => $rolesAndPermissionList['roles'],
        ];

        $response = $this->service->sendRequest($url, $body, $headers);

        return $this->service->parseResponse($response);
    }

    /**
     * @throws TwirpException
     * @throws IntegrationException
     */
    protected function handleCronRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_CRON_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Service-Name' => $this->ba->getInternalApp() ?? '',
            'X-Auth-Type'    => 'internal',
        ];

        $response = $this->service->sendRequest($url, $body, $headers);

        $response = $this->service->parseResponse($response);

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_CRON_RESPONSE, [
            'request'  => $url,
            'response' => $response,
        ]);

        return $response;
    }

    /**
     * @throws TwirpException
     * @throws IntegrationException
     */
    protected function handleLeegalityWebhook($path = null)
    {
        $request = Request::instance();
        $url     = self::LEEGALITY_WEBHOOK_URL;
        $body    = $request->all();

        $headers = [
            'X-Service-Name' => $this->ba->getInternalApp() ?? '',
            'X-Auth-Type'    => 'internal',
        ];

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $response = $this->service->sendRequest($url, $body, $headers);

        return $this->service->parseResponse($response);
    }

    protected function sendMail()
    {
        $request = Request::instance();
        $data    = $request->all();
        if ((isset($data['name']) === false) or
            (isset($data['email']) === false) or
            (isset($data['template']) === false) or
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

