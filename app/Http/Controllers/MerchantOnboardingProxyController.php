<?php

namespace RZP\Http\Controllers;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Core;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;

class MerchantOnboardingProxyController extends BaseProxyController
{

    const MERCHANT_ACTIVATION_SAVE = 'merchant_activation_save';
    const MERCHANT_SIGN_UP = 'merchant_sign_up';
    const PGOS_SHADOW_MODE_EXPERIMENT_ID = 'app.pgos_shadow_mode_experiment_id';
    const ENABLE = 'enable';

    const MERCHANT_ROUTES = [
        self::MERCHANT_ACTIVATION_SAVE,
        self::MERCHANT_SIGN_UP,
    ];

    const ROUTES_URL_MAP    = [
        self:: MERCHANT_ACTIVATION_SAVE            => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantActivationSave',
        self:: MERCHANT_SIGN_UP => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/CreateWorkflow',
    ];

    // timeout in seconds
    const PATH_TIMEOUT_MAP  = [
        self::MERCHANT_ACTIVATION_SAVE                  => .2,
        self:: MERCHANT_SIGN_UP => .2
    ];

    public function __construct()
    {
        parent::__construct("pgos");

        $this->trace = $this->app['trace'];

        $this->registerRoutesMap(self::ROUTES_URL_MAP);

        $this->registerMerchantRoutes(self::MERCHANT_ROUTES);

        $this->setDefaultTimeout(.2);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

    }

    public function handlePGOSProxyRequests($routeKey, $payload, $merchant)
    {
        $merchantId = $merchant->getMerchantId();

        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'merchantId' => $merchantId,
            'routeKey' => $routeKey,
            'payload' => $payload
        ]);

        // check if for the merchant the experiment is enabled or not
        // check if merchant is a regular merchant or not
        if (self::isExperimentEnabled($merchantId, self::PGOS_SHADOW_MODE_EXPERIMENT_ID) and (new Core)->isRegularMerchant($merchant) === true)
        {
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

    protected function isExperimentEnabled($merchantId, $experimentId): bool
    {
        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'splitz_input_experiment_id' => $experimentId,
            'splitz_input_merchant_id' => $merchantId
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

        return $variant === self::ENABLE;
    }
}
