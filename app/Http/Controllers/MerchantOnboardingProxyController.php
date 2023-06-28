<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Core;
use RZP\Models\Admin\Permission\Name;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\Website\Service as WebsiteService;

class MerchantOnboardingProxyController extends BaseProxyController
{

    // route key
    const MERCHANT_ACTIVATION_SAVE       = 'merchant_activation_save';
    const MERCHANT_SIGN_UP               = 'merchant_sign_up';
    const MERCHANT_DOCUMENT_UPLOAD       = 'merchant_document_upload';
    const MERCHANT_DOCUMENT_DELETE       = 'merchant_document_delete';
    const GET_MERCHANT_BMC_RESPONSE      = 'get_merchant_bmc_response';
    const SAVE_MERCHANT_BMC_RESPONSE     = 'save_merchant_bmc_response';
    const GET_CLEARBIT_DOMAIN_INFO       = 'get_clearbit_domain_info';

    const PGOS_SHADOW_MODE_EXPERIMENT_ID = 'app.pgos_shadow_mode_experiment_id';
    const ENABLE                         = 'enable';
    const LIVE                           = 'live';

    const MERCHANT_ROUTES = [
        self::MERCHANT_ACTIVATION_SAVE,
        self::MERCHANT_SIGN_UP,
        self::GET_MERCHANT_BMC_RESPONSE,
        self::SAVE_MERCHANT_BMC_RESPONSE
    ];

    const ADMIN_ROUTES = [
        self::GET_MERCHANT_BMC_RESPONSE,
    ];

    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::GET_MERCHANT_BMC_RESPONSE   => Name::VIEW_ALL_ENTITY,
    ];

    const ROUTES_URL_MAP = [
        self::MERCHANT_ACTIVATION_SAVE         => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantActivationSave',
        self::MERCHANT_SIGN_UP                 => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/CreateWorkflow',
        self::MERCHANT_DOCUMENT_UPLOAD         => 'twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantDocumentUpload',
        self::MERCHANT_DOCUMENT_DELETE         => 'twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantDocumentDelete',
        self::GET_MERCHANT_BMC_RESPONSE        => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantBMCResponse',
        self::SAVE_MERCHANT_BMC_RESPONSE       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SaveMerchantBMCResponse',
        self::GET_CLEARBIT_DOMAIN_INFO         => 'twirp/rzp.pg_onboarding.leads.v1.LeadsService/GetClearbitDomainInfo'
    ];

    // timeout in seconds
    const PATH_TIMEOUT_MAP = [
        self::MERCHANT_ACTIVATION_SAVE      => .2,
        self::MERCHANT_SIGN_UP              => .2,
        self::MERCHANT_DOCUMENT_UPLOAD      => .2,
        self::MERCHANT_DOCUMENT_DELETE      => .2,
        self::GET_MERCHANT_BMC_RESPONSE     => 10,
        self::SAVE_MERCHANT_BMC_RESPONSE    => 10,
        self::GET_CLEARBIT_DOMAIN_INFO      => 10
    ];

    const ROUTES_WITH_PGOS_EXPERIMENT_ALWAYS_ENABLE = [
        self::GET_MERCHANT_BMC_RESPONSE,
        self::SAVE_MERCHANT_BMC_RESPONSE,
    ];

    public function __construct()
    {
        parent::__construct("pgos");

        $this->trace = $this->app['trace'];

        $this->registerRoutesMap(self::ROUTES_URL_MAP);

        $this->registerMerchantRoutes(self::MERCHANT_ROUTES);

        $this->registerAdminRoutes(self::ADMIN_ROUTES, self::ADMIN_ROUTES_VS_PERMISSION);

        $this->setDefaultTimeout(.2);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

    }

    public function handlePGOSProxyRequests($routeKey, $payload, $merchant)
    {
        $merchantId = $merchant->getMerchantId();

        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'merchantId' => $merchantId,
            'routeKey'   => $routeKey,
            'payload'    => $payload
        ]);

        $app = App::getFacadeRoot();

        $mock = $app['config']['pgos.proxy.request.mock'];

        if($mock === true)
        {
            return null;
        }

        // check if for the merchant the experiment is enabled or not
        // check if merchant is a regular merchant or not
        if (($this->isPGOSMigrationExperimentEnabled($merchantId, self::PGOS_SHADOW_MODE_EXPERIMENT_ID,
                                                    self::ENABLE) or
             in_array($routeKey, self::ROUTES_WITH_PGOS_EXPERIMENT_ALWAYS_ENABLE)) and
            (new Core)->isRegularMerchant($merchant) === true)
        {
            // get path from defined route url map
            $twirpPath = self::ROUTES_URL_MAP[$routeKey];

            $route = $this->getRoute($twirpPath);

            $headers = $this->getHeadersForDashboardRequest($payload, $merchantId);

            $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
                'route'     => $route,
                'twirpPath' => $twirpPath,
            ]);

            return $this->sendRequestAndParseResponse($route, 'POST', $twirpPath, $payload, $headers);
        }

    }

    //We are not passing $path here as done in BaseProxyController since we are getting path from request itself.
    //We are passing path params as arguments in this function instead.
    public function handleDashboardProxyRequests($id = '')
    {
        $request = Request::instance();

        $path = $request->getPathInfo();

        if (empty($id) === true)
        {
            $routeKey = str_replace('/v1/pg/onboarding/', '', $path);
        }
        else
        {
            $routeKey = str_replace('/v1/pg/onboarding/' . $id . '/', '', $path);
        }

        $body = $request->all();

        // get path from defined route url map
        $twirpPath = self::ROUTES_URL_MAP[$routeKey];

        $route = $this->getRoute($twirpPath);

        $headers = $this->getHeadersForDashboardRequest($body, $id);

        $this->trace->info(TraceCode::PGOS_DASHBOARD_PROXY_REQUEST, [
            'route'     => $route,
            'twirpPath' => $twirpPath,
        ]);

        try
        {
            $response = $this->sendRequestAndParseResponse($route, 'POST', $twirpPath, $body, $headers);

            $this->routeSpecificPostProcessor($routeKey, $body);

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new ServerErrorException(PublicErrorDescription::SERVER_ERROR, ErrorCode::SERVER_ERROR);
        }
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
        if (in_array($path, $routes) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function getAuthorizationHeader(): string
    {
        return 'Basic ' . base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }

    public function isPGOSMigrationExperimentEnabled($merchantId, $experimentId, $mode): bool
    {
        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'splitz_input_experiment_id' => $experimentId,
            'splitz_input_merchant_id'   => $merchantId
        ]);

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get($experimentId),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'splitz_output' => $variant,
        ]);

        return $variant === $mode;
    }

    /**
     * @param string $routeKey
     * @param array  $body
     */
    private function routeSpecificPostProcessor(string $routeKey, array $body)
    {
        switch ($routeKey)
        {
            case self::SAVE_MERCHANT_BMC_RESPONSE:
                (new WebsiteService())->updateCommonWebsiteQuestions($body, true);
        }
    }

}
