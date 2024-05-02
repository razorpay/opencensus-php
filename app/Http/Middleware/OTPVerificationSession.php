<?php

namespace App\Http\Middleware;

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

    const twoFADisabled="SESSION_DISABLED_2FA";
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
        'post_user_otp_verify'      => ["key" => "success", "value" => true],
        'post_user_verify_contact'  => ["key" => "success", "value" => true],
    ];


    /**
     * @var array $checkUrls will hold the http method prefixed urls for which a session key needs to be checked.
     *                       The keys in this map can be url patterns as well
     */
    public static array $checkUrls = [
        "merchant/api/*/users/2fa" => ["http_method" => "PATCH"]
    ];

    public static array $checkRouteName = [
        // do not add generic route name here
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

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    private function verifyOtpSessionIfApplicable(Request $request): void
    {
        $method = $request->method();
        $routename = optional($request->route())->getName();
        $shouldCheck = false;

        // check for route name for which we need to verify the session
        if($routename !== null)
        {
            foreach (self::$checkRouteName as $name) {
                if ($routename === $name) {
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

                // check for uri pattern
                if ($request->is($patternUri)) {
                    $shouldCheck = true;
                    break;
                }
            }
        }
        // if we need to check and the session key exists, only then we will do the validation
        if ($shouldCheck
                && Session::get(self::OTPVerificationSessionKey) != '1' ) {
            throw new BadRequestError('OTP verification required', ErrorCode::BAD_REQUEST_ERROR, 400);
        }
        if($shouldCheck && Session::get(self::OTPVerificationSessionKey) == '1' &&  $this->isExptEnabled()){
           Session::forget(self::OTPVerificationSessionKey);
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

        // check for route name for which we need to set the session
        if ($routename !== null)
        {
            foreach (self::$setRouteNames as $name => $data) {
                if ($routename === $name) {
                    $this->setOtpSession($this->getOtpSessionValue($content, $data));
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

    private function isExptEnabled():bool{
       try{
           $experimentId = config('splitz.experiments')[self::twoFADisabled];
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
           $concurrentApiCallExperimentId = config('splitz.experiments')[self::twoFADisabled];
           $experimentIds = [$concurrentApiCallExperimentId];
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
