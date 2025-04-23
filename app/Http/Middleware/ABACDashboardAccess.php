<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Session;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use Illuminate\Http\Request;
use Razorpay\Api\Errors\ErrorCode;
use App\Admin\Service as AdminService;
use App\Splitz\Service as SplitzService;
use Razorpay\Api\Errors\BadRequestError;
use App\MerchantDetails\Service as MerchantDetailsService;

/**
 * ABAC (Attribute-Based Access Control) Dashboard Access Middleware
 *
 * This middleware handles access control for the dashboard based on organization and merchant attributes.
 * It implements ABAC by checking various conditions like organization ID, hostname, and route access patterns.
 */
class ABACDashboardAccess
{
    /** Experiment key for ABAC merchant OAuth access control */
    const ABACMerchantOauthAccessExpKey = "ABAC_ACCESS_DASHBOARD";
    
    /** Default Razorpay organization ID */
    const razorpayOrgId = "100000razorpay";
    
    /** Cache key format for merchant details in session */
    const merchantDetailSessionBasedCacheKey = "merchant_details_%s_%s";
    
    /** @var \Illuminate\Foundation\Application */
    protected $app;
    
    /** @var AdminService Service for admin-related operations */
    private AdminService $adminService;
    
    /** @var MerchantDetailsService Service for merchant details operations */
    private MerchantDetailsService $merchantDetailsService;
    
    /** @var SplitzService Service for feature flag and experiment management */
    private SplitzService $splitzService;
    
    /**
     * List of organization hosts that are blacklisted from ABAC checks
     * These hosts will bypass the ABAC logic
     */
    public static array $blackListOrgHost = [
        "admin-dashboard.razorpay.com",
        "x.razorpay.com",
        "partner-lms.razorpay.com",
        "x.dev.razorpay.in",
        "admin-dashboard-int.razorpay.com",
    ];
    
    /**
     * Global access routes that are accessible to all organizations
     * Format: route_pattern => [http_methods => [allowed_methods]]
     */
    public static array $globalAccessRouteUrl = [
        "app/*"                                     => ["http_method" => ["GET"]],
        "user"                                      => ["http_method" => ["GET"]],
        "user/details"                              => ["http_method" => ["GET"]],
        "merchant/api/*/merchants/config/store"     => ["http_method" => ["GET"]],
    ];
    
    /**
     * Organization-specific access routes
     * Format: route_pattern => [http_methods => [allowed_methods], allowed_orgs => [org_ids]]
     */
    public static array $orgAccessRouteUrl = [
//      "merchant/api/*/keys" => ["http_method" => ["GET", "POST"], "allowed_orgs" => ["org_id_hfdc", "org_id_abc"]],
    ];
    
    /**
     * Global access route names that are accessible to all organizations
     * These are route names that bypass ABAC checks
     */
    public static array $globalAccessRouteName = [
//      Do not add generic route name here,
//      This has been added just to load dashboard homepage. post that all api call will fail.
        "dashboard",
        "dashboard_app",
        "user_session",
        "merchant_features",
        "user_details",
        "get_org",
        "merchant_splitz_experiment",
        "merchant_experiment",
        "merchant_tags",
        "merchant_splitz_experiment_v2",
        "merchant_details",
        "user_logout",
        "admin_getIndex",
        "merchants_switch",
        "get_org_by_domain"
    ];
    
    /**
     * Organization-specific access route names
     * Format: route_name => [allowed_orgs => [org_ids]]
     */
    public static array $orgAccessRouteName = [
//     Added for example, This can be extended as per the requirement.
//     "merchant_details" => ["allowed_orgs" => ["org_id_hfdc", "org_id_abc"]],
    ];
    
    /**
     * Constructor for ABACDashboardAccess middleware
     *
     * @param AdminService $adminService Service for admin operations
     * @param MerchantDetailsService $merchantDetailsService Service for merchant details
     * @param SplitzService $splitzService Service for feature flags
     */
    public function __construct(AdminService $adminService, MerchantDetailsService $merchantDetailsService, SplitzService $splitzService)
    {
        $app = \App::getFacadeRoot();
        
        $this->app = $app;
    
        $this->cache =  $app['cache'];
    
        $this->adminService =  $adminService;
        
        $this->merchantDetailsService = $merchantDetailsService;
        
        $this->splitzService = $splitzService;
    }
    
    /**
     * Get current time in milliseconds
     *
     * @return int Current time in milliseconds
     */
    static function millitime(): int
    {
        return round(microtime(true) * 1000);
    }
    
    /**
     * Get the currently logged-in merchant's ID
     *
     * @return string|null Merchant ID if logged in, null otherwise
     */
    private function getLoginMerchantId()
    {
        $user = Auth::user();
        return $user ? $user?->currentMerchant()->id ?? null : null;
    }
    
    /**
     * Generate cache key for merchant's organization ID
     *
     * @param string $currentMerchantId Current merchant ID
     * @return string Cache key for merchant's org ID
     */
    protected function getMerchantOrgIdCacheKey($currentMerchantId): string
    {
        return sprintf(self::merchantDetailSessionBasedCacheKey, $currentMerchantId, Session::getId());
    }
    
    /**
     * Get merchant's organization ID from cache
     *
     * @param string $currentMerchantId Current merchant ID
     * @return string|null Cached organization ID if exists, null otherwise
     */
    protected function getCurrentMerchantOrgIdFromCache($currentMerchantId)
    {
        return $this->cache->get($this->getMerchantOrgIdCacheKey($currentMerchantId));
    }
    
    /**
     * Get current organization ID and related information
     *
     * @return array|null Array containing [org_id, session_ttl, hostname]
     */
    protected function getCurrentOrgId(): ?array
    {
        $domain = \Request::server('SERVER_NAME');
        
        list($error, $org) = $this->adminService->getOrg($domain);
        if (empty($error) === false)
        {
            $this->app['trace']->error(TraceCode::ORG_FETCH_ERROR, ['domain' => $domain, 'error' => $error]);
            return [null, null, null];
        }
        
        $orgId = $org['id'] ?? null;
        $orgMerchantSessionTTL = $org['merchant_session_timeout_in_seconds'] ?? null;
        $orgHostName = $org['hostname'] ?? null;
        if ($orgMerchantSessionTTL === null || $orgMerchantSessionTTL <= 0)
        {
            $orgMerchantSessionTTL = 43200; // default ttl as 12 hr.
        }
        
        return [$orgId, $orgMerchantSessionTTL, $orgHostName];
    }
    
    /**
     * Store merchant's organization ID in cache
     *
     * @param string $currentMerchantId Current merchant ID
     * @param string $currentMerchantOrgId Organization ID to cache
     * @param int $cacheTTL Cache time-to-live in seconds
     */
    protected function setCurrentMerchantOrgIdInCache($currentMerchantId, $currentMerchantOrgId, $cacheTTL): void
    {
        $cacheKey = $this->getMerchantOrgIdCacheKey($currentMerchantId);
        
        $this->cache->put($cacheKey, $currentMerchantOrgId, $cacheTTL);
    }
    
    /**
     * Check if the current request URL or route should be verified for access
     *
     * @param Request $request Current request
     * @param string $currentMerchantOrgId Current merchant's organization ID
     * @return array Array containing [should_check, pattern_uri, route_name]
     */
    public function shouldCheckUrlOrRouteForAccess(Request $request, string $currentMerchantOrgId): array
    {
        $method = $request->method();
        $routename = optional($request->route())->getName();
        $shouldCheck = false;
        $patternUriToBeChecked = "";
        $routeNameToBeCheck = "";
        
        $accessRouteUrl = array_merge(self::$globalAccessRouteUrl, self::$orgAccessRouteUrl);
        
        // check for route name for which we need to processed further.
        if ($routename !== null) {
            foreach (self::$globalAccessRouteName as $name) {
                if ($routename === $name) {
                    $routeNameToBeCheck = $routename;
                    $shouldCheck = true;
                    break;
                }
            }
            
            foreach (self::$orgAccessRouteName as $name => $data) {
                if ($routename === $name && in_array($currentMerchantOrgId, $data["allowed_orgs"]) === true) {
                    $routeNameToBeCheck = $routename;
                    $shouldCheck = true;
                    break;
                }
            }
        }
        
        if (!$shouldCheck) {
            // check for url patterns for which we need to verify the session
            foreach ($accessRouteUrl as $patternUri => $data) {
                $httpMethod = array_get($data, "http_method");
                if (in_array($method,  $httpMethod) === false || $request->is($patternUri) === false)
                {
                    continue;
                }
                
                // Here request pattern is matching.
                $shouldCheck = true;
                $patternUriToBeChecked = $patternUri;
                
                if ((isset($data["allowed_orgs"]) === true) &&
                    (in_array($currentMerchantOrgId, $data["allowed_orgs"]) === false))
                {
                    $shouldCheck = false;
                    $patternUriToBeChecked = "";
                }
                break;
            }
        }
        
        return [
          $shouldCheck,
          $patternUriToBeChecked,
          $routeNameToBeCheck
        ];
    }
    
    /**
     * Get current merchant's organization ID with caching
     *
     * @param string $currentMerchantId Current merchant ID
     * @param int $orgMerchantSessionTTL Session TTL in seconds
     * @return string|null Organization ID if found, null otherwise
     */
    protected function getCurrentMerchantOrgId(string $currentMerchantId, $orgMerchantSessionTTL)
    {
        $startTime = self::millitime();
        $this->app['trace']->info(TraceCode::GET_CURRENT_MERCHANT_ORG_ID_START, ['merchant_id' => $currentMerchantId, 'startTime' => $startTime]);
        
        $currentMerchantOrgId = $this->getCurrentMerchantOrgIdFromCache($currentMerchantId);
        if ($currentMerchantOrgId !== null)
        {
            return $currentMerchantOrgId;
        }
        
        $currentMerchantDetails = $this->merchantDetailsService->getDetailsFromAPIWithCache($currentMerchantId);
        if (empty($currentMerchantDetails) === true)
        {
            $this->app['trace']->info(TraceCode::GET_CURRENT_MERCHANT_DETAILS_NULL, ['merchant_id' => $currentMerchantId, 'endTime' => self::millitime() - $startTime]);
            return null;
        }
        
        $currentMerchantOrgId = $currentMerchantDetails["merchant"]["org_id"] ?? null;
        if ($currentMerchantOrgId === null)
        {
            $this->app['trace']->info(TraceCode::GET_CURRENT_MERCHANT_ORG_ID_NULL, ['merchant_id' => $currentMerchantId, 'endTime' => self::millitime() - $startTime]);
            return null;
        }
        
        $this->setCurrentMerchantOrgIdInCache($currentMerchantId, $currentMerchantOrgId, $orgMerchantSessionTTL);
        
        $this->app['trace']->info(TraceCode::GET_CURRENT_MERCHANT_ORG_ID_END, ['merchant_id' => $currentMerchantId, 'endTime' => self::millitime() - $startTime]);
        return $currentMerchantOrgId;
    }
    
    /**
     * Get the origin host from the request
     *
     * @return string Origin host name
     */
    private function getOriginHost()
    {
        $originRequestUrl = ApiUrl::getRequestOriginUrl();
        return parse_url($originRequestUrl, PHP_URL_HOST);
    }
    
    /**
     * Handle the incoming request
     *
     * @param Request $request Current request
     * @param Closure $next Next middleware in the pipeline
     * @return mixed
     * @throws BadRequestError If access is denied
     */
    public function handle($request, Closure $next): mixed
    {
        $currentMerchantId = $this->getLoginMerchantId();
        if (($currentMerchantId === null) ||
            ($this->isABACMerchantOauthAccessExpEnabled($currentMerchantId, config('splitz.experiments')[self::ABACMerchantOauthAccessExpKey]) === false))
        {
            return $next($request);
        }
    
        [$currentOrgId, $orgMerchantSessionTTL, $orgHostName]  = $this->getCurrentOrgId();
        if ($currentOrgId === null)
        {
            return $next($request);
        }
        
        $currentOrgId = str_replace('org_', '', $currentOrgId);
        if (($currentOrgId !== self::razorpayOrgId) ||
            (in_array($orgHostName, self::$blackListOrgHost) === true) ||
            (in_array($this->getOriginHost(), self::$blackListOrgHost) === true))
        {
            return $next($request);
        }
        
        $currentMerchantOrgId = $this->getCurrentMerchantOrgId($currentMerchantId, $orgMerchantSessionTTL);
        if (($currentMerchantOrgId === null) ||
            ($currentMerchantOrgId === $currentOrgId))
        {
            return $next($request);
        }
        
        [$shouldCheck, $patternUriToBeChecked, $routeNameToBeCheck] = $this->shouldCheckUrlOrRouteForAccess($request, $currentMerchantOrgId);
        if ($shouldCheck === true)
        {
            return $next($request);
        }
    
        $this->app['trace']->info(TraceCode::ACCESS_DENIED_FOR_CROSS_ORG, [
            'current_merchant_id'       => $currentMerchantId,
            'current_login_org_id'      => $currentOrgId,
            'current_merchant_org_id' => $currentMerchantOrgId,
        ]);
        
        throw new BadRequestError('Access Denied For Cross Organization.', ErrorCode::BAD_REQUEST_ERROR, 400);
    }
    
    /**
     * Check if ABAC merchant OAuth access experiment is enabled for a merchant
     *
     * @param string $id Merchant ID
     * @param string $experimentId Experiment ID
     * @return bool True if experiment is enabled, false otherwise
     */
    public function isABACMerchantOauthAccessExpEnabled(string $id, string $experimentId): bool
    {
        try {
            
            $experimentIds = [$experimentId];
            $data = $this->splitzService->getVariantBulk($id, $experimentIds, isSplitzCachingEnabled: true);
  
            if (!array_key_exists($experimentId, $data)) {
                return false;
            }

            return ($data[$experimentId]['variables']['result'] ?? null) === 'on';
        } catch (\Throwable $e) {
            return false;
        }
    }
}