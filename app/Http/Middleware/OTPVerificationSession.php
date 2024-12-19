<?php

namespace App\Http\Middleware;

use App\Http\ApiUrl;
use App\Splitz\Service as SplitzService;
use App\Trace\TraceCode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Razorpay\Api\Errors\ErrorCode;
use Illuminate\Support\Facades\Session;
use Razorpay\Api\Errors\BadRequestError;
use Auth;


class OTPVerificationSession
{
    const OTPVerificationSessionKey = "OTP_VERIFICATION_SESSION";

    const MaxRetryForOtpVerificationSkip = "MAX_RETRY_OTP_VERIFICATION_SKIP";

    const twoFADisabled="SESSION_DISABLED_2FA";

    const MAX_RETRY_VALUE = 10;

    const enablePasswordAndApiKey2fa = "PASSWORD_API_KEY_2FA";
    const enableRouteLinkedAccount2fa = "ROUTE_LINKED_ACCOUNT_2FA";
    /**
     * @var array $setUrls will hold the http method prefixed urls for which a session key needs to be set.
     *                     This is a map of http method prefixed url and a map of key value.
     *                     The key string will determine which value to check inorder to determine if the otp
     *                     verification was successful.
     *                     The keys in this map can be url patterns as well
     */
    public static array $setUrls = [
        "user/verify_contact"                               => ["http_method" => "POST", "key" => "success", "value" => true],
        "user/otp/verify"                                   => ["http_method" => "POST", "key" => "success", "value" => true],
        "merchant/api/*/users/verify/update/new/mobile"     => ["http_method" => "POST", "key" => "success", "value" => true],
    ];

    public static array $setRouteNames = [
        // do not add generic route name here
        'post_user_otp_verify'          => ["key" => "success", "value" => true],
        'post_user_verify_contact'      => ["key" => "success", "value" => true],
    ];


    /**
     * @var array $checkUrls will hold the http method prefixed urls for which a session key needs to be checked.
     *                       The keys in this map can be url patterns as well. This can also optionally contain
     *                       the request body that should be checked.
     */
    public static array $checkUrls = [
        "merchant/api/*/users/2fa"      => ["http_method" => "PATCH"],
        "password"                      => ["http_method" => "POST"],
        "merchant/api/*/keys/rzp_*"     => ["http_method" => "PUT"],
        "merchant/api/*/keys"           => ["http_method" => "POST"],
        "merchant/api/*/invitations"    => ["http_method" => "POST"],
        "submerchants"                  => ["http_method" => "POST"],
        "merchant/api/*/batches"        => ["http_method" => "POST", "body" => ["type" => "linked_account_create"]],
    ];

    public static array $bankingCheckOnRoutes = [
        "merchant/api/*/invitations",
    ];

    public static array $checkRouteName = [
        // do not add generic route name here
    ];


    // NOTE:: Remove this and exp, once all SBB tickets are done and ramped up.
    // This url is subset of checkUrls arrays.
    private static array $newPatternUrlBehindExp = [
        "password",
        "merchant/api/*/keys",
        "merchant/api/*/keys/rzp_*",
        "merchant/api/*/invitations",
        "submerchants"
    ];

    private static array $routeLinkedAccountUrlsBehindExp = [
        "merchant/api/*/batches",
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function handle(Request $request, Closure $next): mixed
    {

        $this->verifyOtpSessionIfApplicable($request);

        $res = $next($request);

        $this->setOtpSessionIfApplicable($request, $res);
        return $res;
    }

    private function shouldCheckUrlForOtpValidation(Request $request): array
    {
        $method = $request->method();
        $routename = optional($request->route())->getName();
        $shouldCheck = false;
        $patternUriToBeChecked = "";
        $routeNameToBeCheck = "";

        // check for route name for which we need to verify the session
        if ($routename !== null) {
            foreach (self::$checkRouteName as $name) {
                if ($routename === $name) {
                    $routeNameToBeCheck = $routename;
                    $shouldCheck = true;
                    break;
                }
            }
        }

        if (!$shouldCheck) {
            // check for url patterns for which we need to verify the session
            foreach (self::$checkUrls as $patternUri => $data) {
                $httpMethod = array_get($data, "http_method");
                // check for http method
                if ($httpMethod !== $method) {
                    continue;
                }

                if ($request->is($patternUri) && isset($data["body"])) {
                    // Check for URI pattern and body both
                    $requestBody = $request->all() ?? [];

                    foreach ($data["body"] as $key => $value) {
                        if (isset($requestBody[$key]) && $requestBody[$key] === $data["body"][$key]) {
                            $patternUriToBeChecked = $patternUri;
                            $shouldCheck = true;
                            break;
                        }
                    }
                }
                else if ($request->is($patternUri)) {
                    // Check for only URI pattern
                    $patternUriToBeChecked = $patternUri;
                    $shouldCheck = true;
                    break;
                }
            }
        }

        return [
            $shouldCheck,
            $patternUriToBeChecked,
            $routeNameToBeCheck
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    private function verifyOtpSessionIfApplicable(Request $request): void
    {
        $isBankingOrigin = ApiUrl::isBankingOriginRequest();

        [$shouldCheck, $patternUriToBeChecked, $routeNameToBeCheck] = $this->shouldCheckUrlForOtpValidation($request);

        if (($shouldCheck === true) and (in_array($patternUriToBeChecked, self::$bankingCheckOnRoutes) === true) and ($isBankingOrigin === true)) {
            return;
        }

        // NOTE: remove this and exp, once all SBB tickets are done and ramped up.
        if ((in_array($patternUriToBeChecked, self::$newPatternUrlBehindExp) === true) &&
            ($this->isExptEnabled(config('splitz.experiments')[self::enablePasswordAndApiKey2fa])=== false))
        {
            return;
        }

        // For Route 2FA flows
        if ((in_array($patternUriToBeChecked, self::$routeLinkedAccountUrlsBehindExp) === true) &&
            ($this->isExptEnabled(config('splitz.experiments')[self::enableRouteLinkedAccount2fa])=== false))
        {
            return;
        }

        // if we need to check and the session key exists, only then we will do the validation
        if ($shouldCheck
            && Session::get(self::OTPVerificationSessionKey) != '1' ) {
            throw new BadRequestError('OTP verification required', ErrorCode::BAD_REQUEST_ERROR, 400);
        }

        if($shouldCheck && Session::get(self::OTPVerificationSessionKey) == '1' &&  $this->isExptEnabled(config('splitz.experiments')[self::twoFADisabled])){
            Session::forget(self::OTPVerificationSessionKey);
        }
    }

    private function resetSessionOtpIfApplicable(Request $request, $content): void
    {
        [$shouldCheck, $patternUriToBeChecked, $routeNameToBeCheck] = $this->shouldCheckUrlForOtpValidation($request);

        if (($shouldCheck === false) ||
            ($content === null)){
            return;
        }

        // Reset otp in session only if non 2xx comes in response.
        $httpStatusCode = array_get($content, "http_status_code");
        $successValue = array_get($content, "success");
        if ($httpStatusCode === null)
        {
            $httpStatusCode = array_get($content, "status_code");
        }

        $retryForOtpVerificationSkip = Session::get(self::MaxRetryForOtpVerificationSkip, 0);

        if (($httpStatusCode !== 200) &&
            ($successValue === false) &&
            ($retryForOtpVerificationSkip > 0)) {
            $this->setOtpSession('1');
            $this->setRetryValueForOtpSkipInSession($retryForOtpVerificationSkip-1);
            return;
        }

        if ($retryForOtpVerificationSkip <= 0) {
            $this->setOtpSession('0');
            Session::forget(self::MaxRetryForOtpVerificationSkip);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $response
     *
     * @return void
     */
    private function setOtpSessionIfApplicable(Request $request, $response): void
    {
        $method = $request->method();
        $routename = optional($request->route())->getName();

        try {
            $content = json_decode($response->getContent(), true);
        }
        catch (\Throwable $e)
        {
            return;
        }

        $this->resetSessionOtpIfApplicable($request, $content);

        // check for route name for which we need to set the session
        if ($routename !== null)
        {
            foreach (self::$setRouteNames as $name => $data) {
                if ($routename === $name) {
                    $this->setOtpSession($this->getOtpSessionValue($content, $data));
                    $this->setRetryValueForOtpSkipInSession(self::MAX_RETRY_VALUE);
                    return;
                }
            }
        }
        // check for url patterns for which we need to set the session
        foreach (self::$setUrls as $patternUri => $data) {
            $httpMethod = array_get($data, "http_method");

            // check for http method
            if ($httpMethod !== $method) {
                continue;
            }
            // check for uri pattern
            if ($request->is($patternUri)) {
                $this->setOtpSession($this->getOtpSessionValue($content, $data));
                $this->setRetryValueForOtpSkipInSession(self::MAX_RETRY_VALUE);
                return;
            }
        }
    }

    private function getOtpSessionValue(array $content, array $dataToCheck): string
    {
        return array_get($content, $dataToCheck['key']) === $dataToCheck['value'] ? '1' : '0';
    }

    private function setOtpSession(string $value): void
    {
        Session::put(self::OTPVerificationSessionKey, $value);
    }

    private function setRetryValueForOtpSkipInSession(int $value): void
    {
        Session::put(self::MaxRetryForOtpVerificationSkip, $value);
    }

    private function isExptEnabled(string $experimentId):bool{
        try{
            if (empty($experimentId)) {
                return true;
            }
            $currentMerchant=Session::get('current_merchant_id');
            if (is_null($currentMerchant))
            {
                return false;
            }
            $user = Auth::user();
            if($user == null){
                return false;
            }
            $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

            if($currentMerchantId == null){
                return false;
            }

            $experimentIds = [$experimentId];
            $data = (new SplitzService())->getVariantBulk($currentMerchantId, $experimentIds,isSplitzCachingEnabled: true);
            if (!array_key_exists($experimentId, $data))
            {
                return false;
            }
            return ($data[$experimentId]['variables']['result'] ?? null) === 'on';
        }
        catch (\Throwable $e){
            return false;
        }
        return false;
    }
}
