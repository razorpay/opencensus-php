<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Foundation\Application;

use RZP\Error\ErrorCode;
use RZP\Http\RequestContext;
use RZP\Http\Route;
use RZP\Http\RequestHeader;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Merchant\Balance\Type as ProductType;
use Lcobucci\JWT\Encoding\CannotDecodeContent;

/**
 * Class ProductIdentifier
 *
 * Serves following functionalities:
 * 1) Identifies product, 2) Initializes request context, and 3) Pushes HTTP metrics.
 * Earlier product was getting set in UserAccess middleware which led to setting of incorrect product in logs
 *
 * @package RZP\Http\Middleware
 */

class ProductIdentifier
{
    const SECRET = 'secret';

    const bankingApps = ['vendor_payments', 'payout_links', 'fts', 'workflows','xpayroll', 'payout_link_customer_page', 'master_onboarding', 'xperience'];

    protected $app;

    /** @var BasicAuth */
    protected $ba;

    /**
     * @var Router
     */
    protected $router;

    /**
     * Laravel request class instance
     * @var Request
     */
    protected $request;

    protected $internalAppName;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->request = $app['request'];

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];
    }

    public function handle($request, Closure $next)
    {
        $start = millitime();

        try
        {
            app('request.ctx')->init();
        } catch (CannotDecodeContent $exception)
        {
            //in case of bad token $parser->parse($token) throws CannotDecodeContent exception.
            return ApiResponse::generateErrorResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID);
        }

        $this->internalAppName = app('request.ctx')->getInternalAppName();

        // Only if request is from internal application dashboard then we understand/process origin header.
        if ($this->isDashboardApp() === true)
        {
            $product = $this->getRequestOriginProductFromRequest($request);

            $this->ba->setRequestOriginProduct($product);

            $this->setIfBankLmsRequest($request);
        }

        // Set product for the request, this is used to tag
        // logs and exceptions with product info
        // Refer ApiTraceProcessor::addProduct()
        $this->deriveAndSetProductFromRequest($request);

        /** @var RequestContext $requestCtx */
        app('request.ctx')->setPaymentMethodV2InRequestContext($request->all());

        $response = $next($request);

        $duration = millitime() - $start;

        (new Throttle)->pushHttpMetrics($request, $response, $duration);

        return $response;
    }

    public function setIfBankLmsRequest(Request $request): void
    {
        $originDomain = $request->headers->get(RequestHeader::X_REQUEST_ORIGIN);
        $this->setIfBankLmsRequestFromOrigin($originDomain);
    }

    public function setIfBankLmsRequestFromOrigin($originDomain): void
    {
        $bankLmsBankingOriginHost = parse_url(config('applications.bank_lms_banking_service_url'), PHP_URL_HOST);

        $requestOriginHost = parse_url($originDomain, PHP_URL_HOST);

        if (empty($requestOriginHost) === false and ($bankLmsBankingOriginHost === $requestOriginHost))
        {
            $this->ba->setBankLms(true);

            return;
        }

        $this->ba->setBankLms(false);
    }

    public function getRequestOriginProductFromRequest(Request $request): string
    {
        $originDomain = $request->headers->get(RequestHeader::X_REQUEST_ORIGIN);
        return $this->getRequestOriginProductFromOrigin($originDomain);
    }

    public function getRequestOriginProductFromOrigin($originDomain): string
    {
        $bankingOriginHost = parse_url(config('applications.banking_service_url'), PHP_URL_HOST);

        $bankLmsBankingOriginHost = parse_url(config('applications.bank_lms_banking_service_url'), PHP_URL_HOST);

        $requestOriginHost = parse_url($originDomain, PHP_URL_HOST);

        $product = ProductType::PRIMARY;

        if (empty($requestOriginHost)===false and (($bankingOriginHost === $requestOriginHost) or ($bankLmsBankingOriginHost === $requestOriginHost)))
        {
            $product = ProductType::BANKING;
        }

        return $product;
    }

    /**
     * Product gives the Product information (payment gateway or business banking).
     * This is derived using $requestOriginProduct and some other parameters like route_name
     * This is useful for tagging logs with respective product info
     * Refer ApiTraceProcessor::addProduct()
     * If request is from PG, product is primary.
     * If request is from X, product is banking.
     *
     * @param $request
     */
    private function deriveAndSetProductFromRequest(Request $request)
    {
        $product = $this->ba->getRequestOriginProduct();

        if ($product === ProductType::BANKING)
        {
            $this->ba->setProduct($product);

            return;
        }

        $isDashboardRoute = $this->isDashboardApp();

        $routeName = $this->router->currentRouteName();

        $bankingRoutes1 = array_keys(Route::$bankingRoutePermissions);

        $bankingRoutes2 = array_values(Route::BANKING_SPECIFIC_ROUTES);

        $bankingRoutes = array_flip(array_merge($bankingRoutes1, $bankingRoutes2));

        // If route is a banking_route, tag it as banking
        // Excluding dashboard routes as in that case, the requestOriginProduct is the source of truth
        if (($isDashboardRoute === false and
            array_key_exists($routeName, $bankingRoutes) === true) or
            in_array($this->internalAppName, self::bankingApps, true) === true)
        {
            $product = ProductType::BANKING;
        }

        /*
         * We pull the user role in UserAccess middleware based on requestOriginProduct.
         * It's required after the 22nd Oct changes as we have a strict check for every route tagged to a defined roles.
         * batch -> api calls like payout_bulk_approve, payout_bulk_create etc authorized on the user roles
         * Todo: Is this required for the internal banking apps in the future?
         */
        if (($this->internalAppName === 'batch' and
           $this->request->headers->get(RequestHeader::X_Creator_Type) == 'user' and
           array_key_exists($routeName, $bankingRoutes) === true))
        {
            $product = ProductType::BANKING;
            $this->ba->setRequestOriginProduct($product);
        }

        /**
         * Flows accessedby Xperience app resolve product from RequestOriginProduct attribute,
         * hence adding this condition here.
         */
        if ($product === ProductType::BANKING and
            $this->internalAppName === 'xperience')
        {
            $this->ba->setRequestOriginProduct(ProductType::BANKING);
        }

        $this->ba->setProduct($product);
    }

    public function isDashboardApp()
    {
        return (in_array($this->internalAppName, BasicAuth::DASHBOARD_APPS) === true ||
                $this->internalAppName === 'master_onboarding');
    }
}
