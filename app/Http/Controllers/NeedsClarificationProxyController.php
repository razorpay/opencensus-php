<?php

namespace RZP\Http\Controllers;

use App;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Core;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;

class NeedsClarificationProxyController extends MerchantOnboardingProxyController
{

    const MERCHANT_ACTIVATION_CLARIFICATION_FETCH           = 'merchant_activation_clarifications_fetch';
    const MERCHANT_ACTIVATION_CLARIFICATIONS_SAVE           = 'merchant_activation_clarifications_save';
    const MERCHANT_ACTIVATION_CLARIFICATIONS_FETCH_ADMIN    = 'merchant_activation_clarifications_fetch_admin';
    const MERCHANT_ACTIVATION_CLARIFICATIONS_SAVE_ADMIN     = 'merchant_activation_clarifications_save_admin';
    const MERCHANT_NC_REVAMP_ELIGIBILITY                    = 'merchant_nc_revamp_eligibility';
    const MERCHANT_NC_REVAMP_ELIGIBILITY_ADMIN              = 'merchant_nc_revamp_eligibility_admin';
    const MERCHANT_UPDATE_CLARIFICATIONS                    = 'merchant_update_clarifications';
    const MERCHANT_ACTIVATION_DOCUMENT_TYPE                 = 'merchant_activation_document_type';


    const MERCHANT_ROUTES = [
        self::MERCHANT_ACTIVATION_CLARIFICATION_FETCH,
        self::MERCHANT_ACTIVATION_CLARIFICATIONS_SAVE,
        self::MERCHANT_ACTIVATION_CLARIFICATIONS_FETCH_ADMIN,
        self::MERCHANT_ACTIVATION_CLARIFICATIONS_SAVE_ADMIN,
        self::MERCHANT_NC_REVAMP_ELIGIBILITY,
        self::MERCHANT_NC_REVAMP_ELIGIBILITY_ADMIN,
        self::MERCHANT_NC_REVAMP_ELIGIBILITY_ADMIN,

    ];

    const ROUTES_URL_MAP    = [
        self:: MERCHANT_ACTIVATION_CLARIFICATION_FETCH         => '/twirp/rzp.pg_onboarding.needsclarification.v1.NeedsClarificationService/GetClarificationReasons',
        self:: MERCHANT_ACTIVATION_CLARIFICATIONS_SAVE         => '/twirp/rzp.pg_onboarding.needsclarification.v1.NeedsClarificationService/PostMerchantResponseToClarifications',
        self:: MERCHANT_ACTIVATION_CLARIFICATIONS_FETCH_ADMIN  => '/twirp/rzp.pg_onboarding.needsclarification.v1.NeedsClarificationService/GetClarificationReasonsAdmin',
        self:: MERCHANT_ACTIVATION_CLARIFICATIONS_SAVE_ADMIN   => '/twirp/rzp.pg_onboarding.needsclarification.v1.NeedsClarificationService/AdminSaveClarificationReasons',
        self:: MERCHANT_NC_REVAMP_ELIGIBILITY                  => '/twirp/rzp.pg_onboarding.needsclarification.v1.NeedsClarificationService/GetMerchantNCRevampEligibility',
        self:: MERCHANT_NC_REVAMP_ELIGIBILITY_ADMIN            => '/twirp/rzp.pg_onboarding.needsclarification.v1.NeedsClarificationService/GetMerchantNCRevampEligibility',
        self:: MERCHANT_ACTIVATION_DOCUMENT_TYPE               => '/twirp/rzp.pg_onboarding.needsclarification.v1.NeedsClarificationService/GetNCAdditionalDocuments',
        self:: MERCHANT_UPDATE_CLARIFICATIONS                  => '/twirp/rzp.pg_onboarding.needsclarification.v1.NeedsClarificationService/UpdateClarificationDetails',
    ];

    // timeout in seconds
    const PATH_TIMEOUT_MAP  = [
        self:: MERCHANT_ACTIVATION_CLARIFICATION_FETCH                  => 10,
        self:: MERCHANT_ACTIVATION_CLARIFICATIONS_SAVE                  => 10,
        self:: MERCHANT_ACTIVATION_CLARIFICATIONS_FETCH_ADMIN           => 10,
        self:: MERCHANT_ACTIVATION_CLARIFICATIONS_SAVE_ADMIN            => 10,
        self:: MERCHANT_NC_REVAMP_ELIGIBILITY                           => 10,
        self:: MERCHANT_NC_REVAMP_ELIGIBILITY_ADMIN                     => 10,
        self:: MERCHANT_UPDATE_CLARIFICATIONS                           => 10,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->trace = $this->app['trace'];

        $this->registerRoutesMap(self::ROUTES_URL_MAP);

        $this->registerMerchantRoutes(self::MERCHANT_ROUTES);

        $this->setDefaultTimeout(10);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

    }

    public function handlePGOSProxyRequests($routeKey, $payload, $merchant, $ignoreRoutingConditions = false)
    {
        $merchantId = $merchant->getMerchantId();

        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'merchantId'    => $merchantId,
            'routeKey'      => $routeKey,
            'payload'       => $payload
        ]);

        $app = App::getFacadeRoot();

        $mock = $app['config']['pgos.proxy.request.mock'];

        if($mock === true)
        {
            return null;
        }

        // get path from defined route url map
        $twirpPath = self::ROUTES_URL_MAP[$routeKey];

        $route = $this->getRoute($twirpPath);

        $headers = $this->getHeadersForDashboardRequest($payload);

        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'route' => $route,
            'twirpPath' => $twirpPath,
        ]);

        return $this->sendRequestAndParseResponse($route, 'POST', $twirpPath, $payload, $headers);

    }

    public function getTwirpRouteName($routeKey)
    {
        return self::MERCHANT_ROUTES[$routeKey];
    }

    /**
     * @throws BadRequestException
     */
    protected function validatePathForRequest($routes, $path)
    {
        if (in_array($path, $routes) === false) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function getAuthorizationHeader(): string
    {
        return 'Basic ' . base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }
}
