<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use RZP\Trace\TraceCode;

class RizeMarketplaceProxyController extends BaseProxyController {
    const FETCH_PRODUCTS            = 'fetch_products';
    const FETCH_PRODUCT_BY_SLUG     = 'fetch_product_by_slug';

    const ROUTES_URL_MAP = [
        self::FETCH_PRODUCTS            => "twirp/rzp.rize.marketplace.v1.MarketPlaceAPI/FetchProducts",
        self::FETCH_PRODUCT_BY_SLUG     => "twirp/rzp.rize.marketplace.v1.MarketPlaceAPI/FetchProductBySlug"
    ];

    const MERCHANT_ROUTES = [
        self::FETCH_PRODUCTS,
        self::FETCH_PRODUCT_BY_SLUG
    ];

    const PATH_TIMEOUT_MAP  = [
        self:: FETCH_PRODUCTS           => 15,
        self:: FETCH_PRODUCT_BY_SLUG    => 15,
    ];

    public function __construct() {

        parent::__construct("rize_service");

        $this->trace = $this->app['trace'];

        $this->registerRoutesMap(self::ROUTES_URL_MAP);

        $this->registerMerchantRoutes(self::MERCHANT_ROUTES);

        $this->setDefaultTimeout(15);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

    }

    public function handleDashboardProxyRequest($path = null) {

        $request = Request::instance();

        $body = $request->all();

        $twirpPath = self::ROUTES_URL_MAP[$path];

        $route = $this->getRoute($twirpPath);

        $headers = $this->getHeadersForDashboardRequest($body);

        $this->trace->info(TraceCode::RIZE_SERVICE_PROXY_REQUEST, [
            'route'     => $route,
            'twirpPath' => $twirpPath,
            'request'   => $request,
        ]);

        $response = $this->sendRequestAndParseResponse($route,'POST', $twirpPath, $body, $headers);

        return $response;
    }

    protected function getAuthorizationHeader() {
        return 'Basic '. base64_encode($this->serviceConfig['username'] . ':' . $this->serviceConfig['password']);
    }

    protected function getBaseUrl(): string {
        return $this->serviceConfig['host'];
    }

    protected function getRequestOrigin() {
        $origin = $_SERVER['HTTP_X_REQUEST_ORIGIN'] ?? null;
        return ($origin && str_contains($origin, 'dashboard')) ? "external" : null;
    }

    protected function getHeadersForDashboardRequest(array $body = [], string $id = '')
    {
        return [
            'x-merchant-id'    => optional($this->ba->getMerchant())->getId() ?? $id,
            'X-Merchant-Email' => optional($this->ba->getMerchant())->getEmail() ?? '',
            'x-user-id'        => optional($this->ba->getUser())->getId() ?? '',
            'X-Auth-Type'      => 'proxy',
            'X-Task-Id'        => $this->app['request']->getTaskId(),
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'Authorization'    => $this->getAuthorizationHeader(),
            'X-Request-ID'     => Request::getTaskId(),
            'X-IP-Address'     => $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip(),
            "X-Request-Source" => $this->getRequestOrigin()
        ];
    }
}
