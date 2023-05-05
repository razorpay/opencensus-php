<?php

namespace RZP\Http\Middleware;

use Closure;
use RZP\Http\BasicAuth;
use Razorpay\Trace\Logger;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use Illuminate\Foundation\Application;

class SDKMetric
{
    const SDK_USAGE   = 'sdk_usage';
    const USER_AGENT  = 'user_agent';
    const MERCHANT_ID = 'merchant_id';

    /**
     * @var Logger
     */
    protected $trace;

    /**
     * This dependency ideally does not belong here and exists here for
     * making assertions during ramp up phase.
     *
     * @var BasicAuth\BasicAuth
     */
    protected $ba;

    /**
     * array of sdk prefixes
     * @var string[]
     */
    protected array $validSdkPrefixes = [
        'Razorpay/v1 PHPSDK/',
        'razorpay-dot-net/',
        'razorpay-go/',
        'Razorpay/v1 JAVASDK/',
        'razorpay-node@',
        'Razorpay-Python/',
        'Razorpay-Ruby/'
    ];

    public function __construct(Application $app)
    {
        $this->trace = $app['trace'];

        $this->ba = $app['basicauth'];
    }

    public function handle($request, Closure $next)
    {
        try
        {
            // user_agent contains the sdk name and version
            $userAgent = $request->userAgent();
            $merchantID = $this->ba->getMerchantId();

            if (Str::startsWith($userAgent, $this->validSdkPrefixes))
            {
                // pushing metric for sdk usage
                $this->trace->count(self::SDK_USAGE, [
                    self::USER_AGENT => $userAgent
                ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::SDK_METRIC_ERROR, ["error" => $e]);
        }
        return $next($request);
    }

}
