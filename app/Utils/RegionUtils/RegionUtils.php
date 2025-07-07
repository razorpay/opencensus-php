<?php
namespace App\Utils\RegionUtils;


use Cookie;
use App\Trace\TraceCode;
use App\Constants\Constants;
use Illuminate\Support\Carbon;
use App\Session\SessionConstants;
use Illuminate\Support\Facades\Date;
use App\Merchant\Service as MerchantService;
use App\Constants\Constants as AppConstants;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * RegionUtils - Utility class for cross-region operations
 *
 * This class provides centralized methods for handling cross-region logic,
 * including session storage decisions, cookie management, and region validation.
 */
class RegionUtils
{

    /**
     * Get the current cell/server region from environment configuration
     *
     * @return string The current cell region
     */
    public static function getCellRegion(): string
    {
        return config('app.cell_region');
    }

    /**
     * Get the current merchant's region
     *
     * @return string|null The merchant's region or null if not available
     */
    public static function getCurrentMerchantRegion(): ?string
    {
        return (new MerchantService)->getCurrentMerchantRegion();
    }

    /**
     * Check if a merchant region is valid
     *
     * @param string $region The merchant region to validate (e.g., 'IN', 'SG', 'US', 'MY')
     * @return bool True if merchant region is valid, false otherwise
     */
    public static function isValidMerchantRegion(string $region): bool
    {
        return in_array($region, RegionConstants::VALID_MERCHANT_REGIONS, true);
    }

    /**
     * Check if merchant region is same as cell region
     *
     * This method now uses the merchant-to-cell mapping to determine if a merchant region
     * is served by the current cell region. This allows multiple merchant regions (like MY and IN)
     * to be served by the same cell region (IN).
     *
     * @param string|null $merchantRegion Optional merchant region to check
     * @return bool True if the merchant region is served by the current cell region, false otherwise
     */
    public static function isMerchantRegionSameAsCellRegion($merchantRegion = null): bool
    {
        if ($merchantRegion === null) {
            $merchantRegion = self::getCurrentMerchantRegion();
        }

        if ($merchantRegion === null) {
            // if merchant region can not be determined then consider same region i.e for non login flows
            return true;
        }

        $cellRegion = self::getCellRegion();

        // Use the mapping logic to check if the merchant region is served by the current cell region
        return RegionConstants::isMerchantRegionServedByCellRegion($merchantRegion, $cellRegion);
    }

    /**
     * Get session storage configuration with migration requirements
     *
     * This method consolidates the logic for determining both storage type and migration needs
     * to avoid duplicate conditional checks and improve clarity.
     *
     * @return array [SessionConstants::STRATEGY_KEY_STORAGE_TYPE => string, SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION => bool, SessionConstants::STRATEGY_KEY_DECISION_REASON => string]
     */
    public static function getSessionStorageConfiguration(): array
    {
        $hasCrossRegionCookie = self::hasCrossRegionCookie() &&
                               self::getCrossRegionCookieValue() === Constants::CROSS_REGION_COOKIE_VALUE_TRUE;
        $hasCrossRegionContext = self::isCrossRegionSetInRequestContext();

        // Case 1: Cookie is set to 'true' - use memory storage, no migration needed
        if ($hasCrossRegionCookie) {
            app('trace')->info(TraceCode::CROSS_REGION_SESSION_STORAGE_USAGE, [
                SessionConstants::STRATEGY_KEY_DECISION_REASON => SessionConstants::DECISION_REASON_CROSS_REGION_COOKIE_SET,
                SessionConstants::STRATEGY_KEY_STORAGE_TYPE => SessionConstants::STORAGE_TYPE_MEMORY_DB,
                SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION => false,
                'cookie_value' => self::getCrossRegionCookieValue()
            ]);

            return [
                SessionConstants::STRATEGY_KEY_STORAGE_TYPE => SessionConstants::STORAGE_TYPE_MEMORY_DB,
                SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION => false,
                SessionConstants::STRATEGY_KEY_DECISION_REASON => SessionConstants::DECISION_REASON_CROSS_REGION_COOKIE_SET
            ];
        }

        // Case 2: Context indicates cross-region but no cookie - use memory storage, migration needed
        if ($hasCrossRegionContext) {
            app('trace')->info(TraceCode::CROSS_REGION_SESSION_STORAGE_TRANSFER, [
                SessionConstants::STRATEGY_KEY_DECISION_REASON => SessionConstants::DECISION_REASON_CROSS_REGION_CONTEXT_WITHOUT_COOKIE,
                SessionConstants::STRATEGY_KEY_STORAGE_TYPE => SessionConstants::STORAGE_TYPE_MEMORY_DB,
                SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION => true,
                'context_value' => app('request.ctx')->getCrossRegion()
            ]);

            return [
                SessionConstants::STRATEGY_KEY_STORAGE_TYPE => SessionConstants::STORAGE_TYPE_MEMORY_DB,
                SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION => true,
                SessionConstants::STRATEGY_KEY_DECISION_REASON => SessionConstants::DECISION_REASON_CROSS_REGION_CONTEXT_WITHOUT_COOKIE
            ];
        }

        app('trace')->info(TraceCode::CROSS_REGION_SESSION_STORAGE_USAGE, [
            SessionConstants::STRATEGY_KEY_DECISION_REASON =>  SessionConstants::DECISION_REASON_SAME_REGION_DEFAULT,
            SessionConstants::STRATEGY_KEY_STORAGE_TYPE => SessionConstants::STORAGE_TYPE_REDIS,
            SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION => false,
            'cookie_value' => self::getCrossRegionCookieValue()
        ]);

        // Case 3: Default - same region, use redis storage, no migration needed
        return [
            SessionConstants::STRATEGY_KEY_STORAGE_TYPE => SessionConstants::STORAGE_TYPE_REDIS,
            SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION => false,
            SessionConstants::STRATEGY_KEY_DECISION_REASON => SessionConstants::DECISION_REASON_SAME_REGION_DEFAULT
        ];
    }

    /**
     * Get the appropriate session storage type based on cross-region requirements
     *
     * @return string SessionConstants::STORAGE_TYPE_REDIS or SessionConstants::STORAGE_TYPE_MEMORY
     */
    public static function getSessionStorageType(): string
    {
        return self::getSessionStorageConfiguration()[SessionConstants::STRATEGY_KEY_STORAGE_TYPE];
    }

    /**
     * Check if session requires migration from Redis to Memory DB
     *
     * @return bool True if session migration is required
     */
    public static function requiresSessionMigration(): bool
    {
        return self::getSessionStorageConfiguration()[SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION];
    }

    /**
     * Set cross-region flag in request context
     */
    public static function setCrossRegionInRequestContext(): void
    {
        if (app()->bound('request.ctx')) {
            app('request.ctx')->setCrossRegion(Constants::CROSS_REGION_COOKIE_VALUE_TRUE);
        }
    }

    /**
     * Set cross-region in request context if applicable (merchant region != cell region)
     *
     * @param string|null $merchantRegion Optional merchant region
     */
    public static function setCrossRegionInRequestContextIfApplicable(?string $merchantRegion = null): bool
    {
        if (!self::isMerchantRegionSameAsCellRegion($merchantRegion)) {
            self::setCrossRegionInRequestContext();
            return true;
        }

        return false;
    }

    /**
     * Check if cross-region is set in request context
     *
     * @return bool True if cross-region is set in context
     */
    public static function isCrossRegionSetInRequestContext(): bool
    {
        if (app()->bound('request.ctx')) {
            return app('request.ctx')->getCrossRegion() === Constants::CROSS_REGION_COOKIE_VALUE_TRUE;
        }

        return false;
    }

    /**
     * Check if cross-region cookie exists
     *
     * @return bool True if cookie exists
     */
    public static function hasCrossRegionCookie(): bool
    {
        return isset($_COOKIE[Constants::RZP_CROSS_REGION]);
    }

    /**
     * Get cross-region cookie value
     *
     * @return string|null The cookie value or null if not set
     */
    public static function getCrossRegionCookieValue(): ?string
    {
        return $_COOKIE[Constants::RZP_CROSS_REGION] ?? null;
    }

    /**
     * Set both region cookies atomically (merchant region + cross-region flag)
     *
     * This method ensures both cookies are always set together to maintain consistency.
     * It determines the appropriate cross-region flag based on merchant region vs cell region.
     *
     * @param mixed $response The response object to set cookies on
     * @param string $merchantRegion The merchant region ('IN', 'MY', 'SG', 'US')
     */
    public static function setRegionCookies($response, $merchantRegion): void
    {
        $config = config('session');
        $expirationDate = self::getCookieExpirationDate($config);
        $cookiePath = $config['path'];
        $isSecure = $config['secure'] ?? false;
        $sameSite = $config['same_site'] ?? null;

        if (!empty($merchantRegion))
        {
            // Set merchant region cookie
            self::setMerchantRegionCookie($response, $merchantRegion, $expirationDate, $cookiePath, $isSecure, $sameSite);
        }

        // Set cross-region cookie (true or false)
        $crossRegionValue = self::isCrossRegionActive($merchantRegion)
            ? Constants::CROSS_REGION_COOKIE_VALUE_TRUE
            : Constants::CROSS_REGION_COOKIE_VALUE_FALSE;


        self::setCrossRegionCookie($response, $crossRegionValue, $expirationDate, $cookiePath, $isSecure, $sameSite);

        app('trace')->info(TraceCode::SETTING_REGION_COOKIES, [
            'merchant_region' => $merchantRegion,
            'cell_region' => self::getCellRegion(),
            'cross_region_value' => $crossRegionValue,
            'is_cross_region_context' => self::isCrossRegionSetInRequestContext()
        ]);
    }


    /**
     * Expire region cookies
     *
     */
    public static function expireRegionCookies(): void
    {
        // Expire merchant region cookie
        Cookie::expire(Constants::RZP_USER_MERCHANT_REGION, AppConstants::ROOT_PATH);

        // Expire cross-region cookie
        Cookie::expire(Constants::RZP_CROSS_REGION, AppConstants::ROOT_PATH);

        app('trace')->info(TraceCode::CROSS_REGION_SESSION_STORAGE_USAGE, [
            'action' => 'expire_region_cookies',
            'merchant_region' => 'expired',
            'cross_region_value' => 'expired'
        ]);
    }

    /**
     * Set merchant region cookie
     *
     * @param mixed $response Response object
     * @param string $merchantRegion Merchant region value
     * @param \DateTimeInterface $expirationDate Cookie expiration
     * @param string $cookiePath Cookie path
     * @param bool $isSecure Is secure cookie
     * @param string|null $sameSite SameSite attribute
     */
    private static function setMerchantRegionCookie($response, string $merchantRegion, \DateTimeInterface $expirationDate, string $cookiePath, bool $isSecure, ?string $sameSite): void
    {
        $response->headers->setCookie(new SymfonyCookie(
            Constants::RZP_USER_MERCHANT_REGION,
            $merchantRegion,
            $expirationDate,
            $cookiePath,
            null,
            $isSecure,
            false,
            false,
            $sameSite
        ));
    }

    /**
     * Set cross-region cookie
     *
     * @param mixed $response Response object
     * @param string $crossRegionValue Cross-region cookie value
     * @param \DateTimeInterface $expirationDate Cookie expiration
     * @param string $cookiePath Cookie path
     * @param bool $isSecure Is secure cookie
     * @param string|null $sameSite SameSite attribute
     */
    private static function setCrossRegionCookie($response, string $crossRegionValue, \DateTimeInterface $expirationDate, string $cookiePath, bool $isSecure, ?string $sameSite): void
    {
        $response->headers->setCookie(new SymfonyCookie(
            Constants::RZP_CROSS_REGION,
            $crossRegionValue,
            $expirationDate,
            $cookiePath,
            null,
            $isSecure,
            false,
            false,
            $sameSite
        ));
    }

    /**
     * Determine if cross-region functionality should be active
     *
     * This method determines whether cross-region session storage should be used
     * by checking existing cookie state and regional context.
     *
     * @param string $merchantRegion The merchant region
     * @return bool True if cross-region functionality should be active
     */
    private static function isCrossRegionActive($merchantRegion): bool
    {
        // If cookie already exists and is 'true', preserve it to maintain cross-region flow
        // This prevents breaking the flow after traffic is routed to the correct regional cell
        // if cross region is set in request context: register
        $existingCookieValue = self::getCrossRegionCookieValue();
        if ($existingCookieValue === Constants::CROSS_REGION_COOKIE_VALUE_TRUE || self::isCrossRegionSetInRequestContext()) {
            return true;
        }

        if (!empty($merchantRegion) and !self::isMerchantRegionSameAsCellRegion($merchantRegion)){
            RegionUtils::setCrossRegionInRequestContext();
            return true;
        }

        // Activate cross-region if merchant region != cell region OR if cross-region context is set
        return false ;
    }

    /**
     * Get cookie expiration date
     *
     * @param array|null $config Session configuration
     * @return \DateTimeInterface Cookie expiration date
     */
    private static function getCookieExpirationDate(?array $config = null): \DateTimeInterface
    {
        if ($config === null) {
            $config = config('session');
        }

        // same as user session cookie
        return Date::instance(Carbon::now()->addRealMinutes($config['lifetime']));
    }
}
