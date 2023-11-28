<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Predis\PredisException;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Route;
use RZP\Services\SplitzService;
use RZP\Trace\TraceCode;
use Symfony\Component\HttpFoundation\Response;

class StartSession extends BaseStartSession
{
    /** @var BasicAuth $ba */
    protected $ba;

    /** @var ConfigRepository $config  */
    protected $config;

    /** @var SplitzService $splitz */
    protected $splitz;

    /** @var Trace $trace */
    protected $trace;

    protected string $route;

    protected bool $isSessionPersistent = true;

    /**
     * List of public routes called by checkout FE, using session for global customers.
     *
     * @var string[]
     */
    protected array $checkoutSessionRoutes = [
        '1cc_apply_gift_card' => true,
        '1cc_customer_truecaller_verify' => true,
        '1cc_remove_gift_card' => true,
        '1cc_shopify_checkout' => true,
        '1cc_shopify_order' => true,
        'checkout_personalisation' => true,
        'customer_create_global_address' => true,
        'customer_edit_global_address' => true,
        'customer_get_saved_status' => true,
        'customer_record_1cc_address_consent' => true,
        'customer_record_1cc_address_consent_view' => true,
        'customer_update_global' => true,
        'merchant_coupon_validity' => true,
        'offers_fetch_for_order' => true,
        'order_update_customer_details_1cc' => true,
        'payment_calculate_fees' => true,
        'payment_create' => true,
        'payment_create_ajax' => true,
        'payment_create_checkout' => true,
        'payment_create_fees' => true,
        'payment_create_jsonp' => true,
        'record_1cc_customer_consent' => true,
        'shipping_info' => true,
    ];

    /**
     * @inheritDoc
     */
    public function __construct(SessionManager $manager, callable $cacheFactoryResolver = null)
    {
        parent::__construct($manager, $cacheFactoryResolver);

        $this->ba = app('basicauth');
        $this->config = app('config');
        $this->splitz = app('splitzService');
        $this->trace = app('trace');
    }

    public function handle($request, Closure $next)
    {
        try
        {
            $this->isSessionPersistent = $this->shouldSaveSession($request);

            return parent::handle($request, $next);
        }
        catch (PredisException $e) // Catching PredisException thrown by predis.
        {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::SESSION_CREATE_ERROR_FROM_CACHE, []);

            return $next($request);
        }
    }

    /**
     * @inheritDoc
     */
    protected function addCookieToResponse(Response $response, Session $session): void
    {
        if (!$this->isSessionPersistent) {
            return;
        }

        parent::addCookieToResponse($response, $session);
    }

    /**
     * @inheritDoc
     */
    protected function getCookieExpirationDate(): \DateTimeInterface|int
    {
        if ($this->route === 'customer_logout_global') {
            // Instruct browser to expire session & delete cookie immediately
            return Date::instance(
                Carbon::now()->subRealYears(5) // -5 years
            );
        }

        return parent::getCookieExpirationDate();
    }

    /**
     * @inheritDoc
     */
    protected function saveSession($request): void
    {
        if (!$this->isSessionPersistent) {
            return;
        }

        parent::saveSession($request);
    }

    protected function shouldSaveSession(Request $request): bool
    {
        $this->route = optional($request->route())->getName() ?? '';

        if ($this->isRequestFromSdkToPreferences($request, $this->route)) {
            // Do not store a session in cache if the request is coming from
            // PHP SDK to /v1/preferences endpoint
            return false;
        }

        return $this->shouldSaveCustomerSession($request, $this->route);
    }

    protected function isRequestFromSdkToPreferences(Request $request, string $route): bool
    {
        $userAgent = $request->userAgent();

        if (($route === 'merchant_checkout_preferences') &&
            (
                Str::startsWith($userAgent, 'Razorpay/v1 PHPSDK/') ||
                Str::startsWith($userAgent, 'Razorpay/v1 JAVASDK/')
            )
        ) {
            $this->trace->info(TraceCode::SDK_CALL_TO_PREFERENCES_ENDPOINT, [
                'key_id'     => $request->input('key_id'),
                'user_agent' => $userAgent,
                'message'    => 'Not storing session in cache',
            ]);

            return true;
        }

        return false;
    }

    protected function shouldSaveCustomerSession(Request $request, string $routeName): bool
    {
        $customerLoginLogoutRoutes = [
            '1cc_customer_truecaller_verify' => true,
            '1cc_otp_verify' => true,
            'customer_logout_global' => true,
            'customer_truecaller_verify' => true,
            'otp_verify' => true,
            'support_page_otp_verify' => true,
        ];
        $isCustomerLoginLogoutRoute = $customerLoginLogoutRoutes[$routeName] ?? false;
        if ($isCustomerLoginLogoutRoute) {
            return true;
        }

        // @ToDo: Remove after removing these routes from Route::$session
        $sessionAllowedInternalRoutes = [
            'customer_fetch_internal_for_checkout' => true,
            'global_customer_find_or_create_for_checkout' => true,
        ];

        if (!empty($sessionAllowedInternalRoutes[$routeName])) {
            // Do not save customer session on internal routes.
            return false;
        }

        /** @var string $csMode The mode checkout-service is running in. */
        $csMode = $request->header('X-Checkout-Service-Mode', 'live');

        if (($csMode === 'shadow') ||
            in_array($routeName, Route::$internalApps['checkout_service'], true)
        ) {
            // Do not store a session in cache if checkout-service is running in
            // shadow mode (or) the request is coming to internal routes called
            // in parallel by the checkout-service except for the customer fetch
            // route. They are part of Route::$session only to ensure they are
            // able to detect a global customer & apply business logic accordingly.

            return false;
        }

        $isCheckoutSessionRoute = $this->checkoutSessionRoutes[$routeName] ?? false;

        if ($isCheckoutSessionRoute || $request->cookies->has('razorpay_api_session_v2')) {
            // Do not save customer session for public routes called by checkout FE
            return false;
        }

        return true;
    }
}
