<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;

class MerchantExperienceProxyController extends BaseProxyController {

    const MERCHANT_KYC_DETAILS_FETCH           = 'merchant_kyc_details_fetch';
    const MERCHANT_KYC_DETAILS_UPDATE          = 'merchant_kyc_details_update';
    const MERCHANT_KYC_DETAILS_FETCH_ADMIN           = 'merchant_kyc_details_fetch_admin';
    const MERCHANT_KYC_DETAILS_UPDATE_ADMIN         = 'merchant_kyc_details_update_admin';

    // constants for mock repsponse
    const MSG              = 'msg';
    const CODE             = 'code';
    const DOWNSTREAM_STATUS_CODE = 'downstream_status_code';
    const META             = 'meta';

    const ROUTES_URL_MAP    = [
        self:: MERCHANT_KYC_DETAILS_FETCH         => 'rzp.merchant_experience_service.kyc_details.v1.KycDetailsService/GetKycDetails',
        self:: MERCHANT_KYC_DETAILS_UPDATE         => 'rzp.merchant_experience_service.kyc_details.v1.KycDetailsService/UpdateKycDetails',
        self:: MERCHANT_KYC_DETAILS_FETCH_ADMIN         => 'rzp.merchant_experience_service.admin.v1.KycDetailsService/GetKycDetails',
        self:: MERCHANT_KYC_DETAILS_UPDATE_ADMIN        => 'rzp.merchant_experience_service.admin.v1.KycDetailsService/UpdateKycDetails',
    ];
    const MERCHANT_ROUTES = [
        self::MERCHANT_KYC_DETAILS_FETCH,
        self::MERCHANT_KYC_DETAILS_UPDATE,
    ];

    const ADMIN_ROUTES = [
        self::MERCHANT_KYC_DETAILS_FETCH_ADMIN,
        self::MERCHANT_KYC_DETAILS_UPDATE_ADMIN,
    ];

    const PATH_TIMEOUT_MAP  = [
        self:: MERCHANT_KYC_DETAILS_FETCH                  => 15,
        self:: MERCHANT_KYC_DETAILS_UPDATE                  => 15,
    ];

    const MERCHANT_ROUTES_TO_ADMIN_ROUTE    = [
        self:: MERCHANT_KYC_DETAILS_FETCH         => self::MERCHANT_KYC_DETAILS_FETCH_ADMIN,
        self:: MERCHANT_KYC_DETAILS_UPDATE         => self::MERCHANT_KYC_DETAILS_UPDATE_ADMIN,
    ];

    public function __construct()
    {
        parent::__construct("merchant-experience-service");

        $this->trace = $this->app['trace'];

        $this->registerRoutesMap(self::ROUTES_URL_MAP);

        $this->setDefaultTimeout(20);

        $this->registerMerchantRoutes(self::MERCHANT_ROUTES);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

    }

    protected function mesMockResponses(string $routeKey)
    {
        //mocking default response based on RouteKey
        return match ($routeKey)
        {
            self::MERCHANT_KYC_DETAILS_FETCH => [
                self::CODE                   => "internal",
                self::MSG                    => "true",
                self::DOWNSTREAM_STATUS_CODE => 500,
                self::META                   => ["cause" => "errors.Error"]
            ],

            default => null,
        };
    }

    public function getCurrentSelfServeReKYCMerchantStatus(string $merchantId): ?string
    {
        $selfServeReKYCDetails = $this->getCurrentSelfServeReKYCMerchantDetails($merchantId);
        $this->app['trace']->info(TraceCode::SELF_SERVE_REKYC_STATUS,[
            "selfServeReKYCDetails" => $selfServeReKYCDetails,
        ]);
        if ($selfServeReKYCDetails !== null && isset($selfServeReKYCDetails['data'][0])) {
            return $selfServeReKYCDetails['data'][0]['status'] ?? null;
        }
        return null;
    }

    private function getCurrentSelfServeReKYCMerchantDetails(string $merchantId): ?array
    {
        $mesResponse = $this->handleMESProxyRequests(self::MERCHANT_KYC_DETAILS_FETCH, [
            'merchant_id' => $merchantId,
            'is_current' => true
        ]);

        if ($mesResponse === null) {
            $this->app['trace']->info(TraceCode::SELF_SERVE_REKYC_FETCH_FAILED, [
                'merchant_id' => $merchantId,
                'reason' => 'null_response'
            ]);
            return null;
        }

        if (is_array($mesResponse) &&
            isset($mesResponse['downstream_status_code']) &&
            $mesResponse['downstream_status_code'] === 200 &&
            isset($mesResponse['data']) &&
            !empty($mesResponse['data'])) {

            return $mesResponse;
        }

        $this->app['trace']->info(TraceCode::SELF_SERVE_REKYC_FETCH_FAILED, [
            'merchant_id' => $merchantId,
            'reason' => 'invalid_response_structure',
            'response' => $mesResponse
        ]);
        return null;
    }

    public function UpdateReKYCDetailSelfServeReKYCMerchant($merchantId, $status): bool
    {
        $mesResponse = $this->handleMESProxyRequests(self::MERCHANT_KYC_DETAILS_UPDATE, [
            'merchant_id' => $merchantId,
            'status' => $status,
        ]);

        $this->app['trace']->info(TraceCode::SELF_SERVE_REKYC_UPDATE_RESPONSE, [
            'merchant_id' => $merchantId,
            'status' => $status,
            'response' => $mesResponse
        ]);

        if ($mesResponse === null) {
            return false;
        }

        if (isset($mesResponse['downstream_status_code']) &&
            $mesResponse['downstream_status_code'] === 200) {

            return true;
        }

        return false;
    }
    public function isSelfServeReKYCMerchant($merchantId): bool
    {
        $mesResponse = $this->handleMESProxyRequests(self::MERCHANT_KYC_DETAILS_FETCH, [
            'merchant_id' => $merchantId,
            'is_current' => true
        ]);

        if ($mesResponse === null) {
            return false;
        }

        if (isset($mesResponse['downstream_status_code']) &&
            $mesResponse['downstream_status_code'] === 200 &&
            isset($mesResponse['data']) &&
            !empty($mesResponse['data'])) {
            return true;
        }

        $this->app['trace']->info(TraceCode::SELF_SERVE_REKYC_FETCH_FAILED, [
            'merchant_id' => $merchantId,
            'reason' => 'invalid_response_structure',
            'response' => $mesResponse
        ]);
        return false;
    }

    private function handleMESProxyRequests($routeKey, $payload)
    {
        if(self::isAdminAuth()) {
            $routeKey = self::MERCHANT_ROUTES_TO_ADMIN_ROUTE[$routeKey];
        }

        $this->trace->info(TraceCode::MES_PROXY_REQUEST, [
            'routeKey' => $routeKey,
            'payload' => $payload
        ]);

        $headers = [
            'X-Razorpay-Mode' => $this->ba->getMode() ?? 'live',
            'X-Task-Id' => $this->app['request']->getTaskId(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $this->getAuthorizationHeader(),
        ];
        if(self::isAdminAuth()) {
            $headers['X-User-Id'] = optional($this->app['basicauth']->getAdmin())->getId() ?? '';
            $headers['X-User-Email'] = optional($this->app['basicauth']->getAdmin())->getEmail() ?? '';
        } else {
            $headers['X-User-Id'] = optional($this->app['basicauth']->getUser())->getId() ??  optional($this->app['basicauth']->getMerchant())->getId() ?? '';
            $headers['X-Razorpay-Merchant-Id'] = optional($this->app['basicauth']->getMerchant())->getId() ?? '';
        }

        $twirpPath = self::ROUTES_URL_MAP[$routeKey];
        $route = $this->getRoute($twirpPath);

        $this->trace->info(TraceCode::MES_PROXY_REQUEST, [
            'route' => $route,
            'twirpPath' => $twirpPath,
            'headers' => $headers
        ]);

        try {
            $response = $this->sendRequestAndParseResponse($route, 'POST', $twirpPath, $payload, $headers);
            $this->trace->info(TraceCode::MES_PROXY_RESPONSE, [
                'route' => $route,
                'merchantId' => $payload['merchant_id'],
                'response' => $response
            ]);
            return $response;
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::MES_PROXY_ERROR, [
                'error_message' => $e->getMessage()
            ]);
        }
        return null;
    }

    private function isAdminAuth(): bool
    {
        return $this->app['basicauth']->isAdminAuth();
    }

    protected function getAuthorizationHeader()
    {
        if(self::isAdminAuth()){
            return 'Basic ' . base64_encode($this->serviceConfig['admin_user'] . ':' . $this->serviceConfig['admin_password']);
        }
        return 'Basic ' . base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }
}
