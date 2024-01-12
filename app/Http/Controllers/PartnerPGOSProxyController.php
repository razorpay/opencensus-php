<?php

namespace RZP\Http\Controllers;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;

class PartnerPGOSProxyController extends MerchantOnboardingProxyController
{
    const PARTNER_POS_DEVICE_CONFIG_CREATE = 'partner_pos_device_config_create';
    const PARTNER_POS_DEVICE_CONFIG_UPDATE = 'partner_pos_device_config_update';

    const PARTNER_ROUTES = [
        self::PARTNER_POS_DEVICE_CONFIG_CREATE,
        self::PARTNER_POS_DEVICE_CONFIG_UPDATE
    ];

    const ROUTES_URL_MAP    = [
        self::PARTNER_POS_DEVICE_CONFIG_CREATE => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/CreateDeviceConfig',
        self::PARTNER_POS_DEVICE_CONFIG_UPDATE => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/UpdateDeviceConfig',
    ];

    const PATH_TIMEOUT_MAP  = [
        self::PARTNER_POS_DEVICE_CONFIG_CREATE => 15,
        self::PARTNER_POS_DEVICE_CONFIG_UPDATE => 15,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->trace = $this->app['trace'];

        $this->registerRoutesMap(self::ROUTES_URL_MAP);

        $this->registerMerchantRoutes(self::PARTNER_ROUTES);

        $this->setDefaultTimeout(15);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

    }

    public function handlePGOSProxyRequests($routeKey, $payload, $merchant, $ignoreRoutingConditions = false)
    {
        $merchantId = $merchant->getMerchantId();

        $this->trace->info(TraceCode::PARTNER_PGOS_PROXY_REQUEST, [
            'partner_id'    => $merchantId,
            'route_key'      => $routeKey,
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

        $this->trace->info(TraceCode::PARTNER_PGOS_PROXY_REQUEST, [
            'route' => $route,
            'twirpPath' => $twirpPath,
        ]);

        return $this->sendRequestAndParseResponse($route, 'POST', $twirpPath, $payload, $headers);
    }


    public function getTwirpRouteName($routeKey)
    {
        return self::PARTNER_ROUTES[$routeKey];
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
