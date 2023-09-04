<?php

namespace RZP\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Str;
use Predis\PredisException;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Route;
use RZP\Models\Base\UniqueIdEntity;
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

    protected bool $isSessionPersistent = true;

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
    protected function saveSession($request): void
    {
        if (!$this->isSessionPersistent) {
            return;
        }

        parent::saveSession($request);
    }

    protected function shouldSaveSession(Request $request): bool
    {
        $route = optional($request->route())->getName() ?? '';

        if ($this->isRequestFromSdkToPreferences($request, $route)) {
            // Do not store a session in cache if the request is coming from
            // PHP SDK to /v1/preferences endpoint
            return false;
        }

        return $this->shouldSaveCustomerSession($request, $route);
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

        // @ToDo: Remove after customer session decomposition 100% ramp-up
        $sessionAllowedInternalRoutes = [
            'customer_fetch_internal_for_checkout' => $this->config->get('app.stop_session_redis_usage_on_customer_fetch_internal_experiment_id'),
            'global_customer_find_or_create_for_checkout' => $this->config->get('app.stop_session_redis_usage_on_global_customer_find_or_create_experiment_id'),
        ];

        if (!empty($sessionAllowedInternalRoutes[$routeName])) {
            $variant = $this->getSplitzExperimentResult($sessionAllowedInternalRoutes[$routeName], $request);

            // If variant is on then we do not save the session on API Monolith
            return $variant !== 'variant_on';
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

        if ($request->cookies->has('razorpay_api_session_v2')) {
            // Skip Saving to Redis if v2 session cookie is present in the
            // request as v2 sessions are now managed by checkout-service
            $variant = $this->getSplitzExperimentResult(
                $this->config->get('app.stop_session_redis_usage_on_all_other_routes_experiment_id'),
                $request,
            );

            // If variant is on then we do not save the session on API Monolith
            return $variant !== 'variant_on';
        }

        return true;
    }

    protected function getSplitzExperimentResult(string $experimentId, Request $request): string
    {
        try {
            $properties = [
                'id' => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $experimentId,
                'request_data' => json_encode([
                    'key_id' => $request->input('key_id'),
                    'merchant_id' => $this->ba->getMerchantId(),
                ], JSON_THROW_ON_ERROR),
            ];

            $response = $this->splitz->evaluateRequest($properties);
        } catch (Exception $e) {
            $this->trace->traceException(
                $e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                    'experiment_id' => $experimentId,
                ]
            );

            return '';
        }

        return $response['response']['variant']['name'] ?? '';
    }
}
