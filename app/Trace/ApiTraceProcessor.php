<?php

namespace RZP\Trace;

use App;
use Request;
use Route as RouteFacade;
use OpenCensus\Trace\Tracer;

use RZP\Jobs\Context;
use RZP\Http\RequestHeader;
use RZP\Http\Route;
use RZP\Constants\Tracing;
use RZP\Constants\Product;
use Razorpay\Trace\Logger;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;

class ApiTraceProcessor
{
    protected $app;

    //
    // This regex is copied from
    // https://adamcaudill.com/2011/10/20/masking-credit-cards-for-pci/
    //
    // Sample string: 'CCPAY.4000000000000002@icici'
    //
    const CCPAY_CARD_REGEX = "/CCPAY.(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|" .
                             "6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|" .
                             "[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})/";

    //
    // This regex is taken from https://www.regular-expressions.info/creditcard.html,
    // https://service.in.sumologic.com/ui/#/search/qaUTvs26bqWnSxgNCOIXnMFOtjLjBCGxT2GYDywC
    // This regex is used to scrub credit card numbers from logs.
    // Currently only banking specific routes will be affected by this
    //
    const CARD_REGEX = "/\b(?:4[0-9]{12}(?:[0-9]{3})?" .         # Visa
                       "|(?:5[1-5][0-9]{2}" .                # MasterCard
                       "|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}" .
                       "|3[47][0-9]{13}" .                   # Amex
                       "|3(?:0[0-5]|[68][0-9])[0-9]{11}" .   # Diners Club
                       "|6(?:011|5[0-9]{2})[0-9]{12}" .      # Discover
                       "|(?:2131|1800|35\d{3})\d{11}" .      # JCB
                       ")\b/";

    //This regex is taken from https://emailregex.com/
    //This regex is used to scrub sensitive banking details from logs and stack trace too.
    //Current enabled only for banking routes.
    const EMAIL_REGEX = "/(?:[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&'*+\/=?" .
                        "^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-" .
                        "\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[A-Za-" .
                        "z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-" .
                        "9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}" .
                        "(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[A-Za-z0-9-]*[A-Za-z0-9]:" .
                        "(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-" .
                        "\x09\x0b\x0c\x0e-\x7f])+)\])/";

    //This regex will check for 3 or 4 digits and will scrub only if before the digits .php( is not present.
    //This has done to avoid scrubbing stack trace line numbers.
    const CVV_REGEX = "/\b(?<!\.php\()\d{3,4}\b/";

    const PHONE_NUMBER_REGEX = "/(\b|\+91)\d{10}\b/";

    const EMAIL_SCRUBBED = "EMAIL_SCRUBBED";

    const CVV_SCRUBBED = "CVV_SCRUBBED";

    const PHONE_NUMBER_SCRUBBED = "PHONE_NUMBER_SCRUBBED";

    //
    // This is added to disable scrubbing.let's say we decided to kill this feature for some reason , bad RegEx or
    // what-ever. if we set it to "null" , we will expect it to not do RegEx.. however since there is a default regex,
    // it will always run. only choice is to go into code / deploy again. In order to avoid that ,adding a magic string
    // off. if redis value for key CREDIT_CARD_REGEX_FOR_REDACTING returns off , we will disable scrubbing
    //
    const OFF = 'off';

    const SENSITIVE_KEYS = [
        'iin',
        'expiry_month',
        'expiry_year',
        'account_number',
        'name',
        'account_ifsc',
        'beneficiary_name',
        'beneficiary_address1',
        'beneficiary_address2',
        'beneficiary_address3',
        'payer_name',
        'beneficiaryAccountNo',
        'debitAccountNo',
        'beneficiaryName',
        'beneficiaryContact',
        'password',
        'client_secret',
        'payer_account_number',
        'otp',
        'fund_account_name',
        'fund_account_number',
        'contact.partial_search',
    ];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function __invoke(array $record)
    {
        $this->addMode($record);

        $this->addOAuthAttributes($record);

        $this->addPartnerAttributes($record);

        $this->updateClientIp($record);

        $this->addMerchantId($record);

        $this->addDashboardHeaders($record);

        $this->addProduct($record);

        $this->addTraceId($record);

        $this->addDistributedTraceId($record);

        $this->addAwsTraceId($record);

        $this->addAwsTlsVersion($record);

        $this->addRazorpayRequestId($record);

        $this->addRouteNameForExceptions($record);

        $this->scrubCardNumberViaCcPay($record);

        $this->scrubSensitiveInfoForBankingRoutes($record);

        $this->overrideRequestAttributes($record);

        $this->keysBasedScrubbingForBankingRoutes($record);

        $this->addTraceAttributesForWorkers($record);

        return $record;
    }

    protected function addMode(& $record)
    {
        $record['mode'] = $this->app['basicauth']->getMode();
    }

    protected function addOAuthAttributes(& $record)
    {
        $accessTokenId = $this->app['basicauth']->getAccessTokenId();

        if (empty($accessTokenId) === false)
        {
            $record['request']['access_token_id'] = $accessTokenId;
        }

        $oauthClientId = $this->app['basicauth']->getOAuthClientId();

        if (empty($oauthClientId) === false)
        {
            $record['request']['oauth_client_id'] = $oauthClientId;
        }
    }

    protected function addPartnerAttributes(& $record)
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        if (empty($partnerMerchantId) === false)
        {
            $record['request']['partner_merchant_id'] = $partnerMerchantId;
        }
    }

    protected function updateClientIp(& $record)
    {
        $record['request']['client_ip'] = $this->app['request']->ip();
    }

    protected function addMerchantId(& $record)
    {
        $record['request']['merchant_id'] = $this->app['basicauth']->getMerchantId();
    }

    protected function addDashboardHeaders(&$record)
    {
        if ($this->app['basicauth']->isDashboardApp() === true)
        {
            $record['request'] += $this->app['basicauth']->getDashboardHeaders();

            $record['request']['dashboard_app'] = $this->app['basicauth']->getInternalApp() ?? '';
        }
    }

    protected function addProduct(&$record)
    {
        $product = $this->app['basicauth']->getProduct();

        $record['request']['product'] = $product;
    }

    protected function addRouteNameForExceptions(& $record)
    {
        // If this is an exception, a stack key is present in the context array
        $isException = (
            isset($record['context']['stack'])
            or (isset($record['message']) and $record['message'] === TraceCode::ERROR_RESPONSE_DATA)
            or (isset($record['code']) and $record['code'] === TraceCode::ERROR_EXCEPTION)
        );

        if ($isException === true)
        {
            $record['request']['route_name'] = optional($this->app['router'])->currentRouteName();
            $record['request']['method'] = $this->app->request->method();
        }
    }

    protected function scrubCardNumberViaCcPay(& $record)
    {
        $context = $record['context'] ?? null;

        if (empty($context) === true)
        {
            return;
        }

        array_walk_recursive($context, function(& $item)
        {
            if (is_string($item) === true)
            {
                if (preg_match(self::CCPAY_CARD_REGEX, $item) === 1)
                {
                    $item = 'CARD_NUMBER_SCRUBBED';
                }
            }
        });

        $record['context'] = $context;
    }

    protected function scrubSensitiveInfoForBankingRoutes(& $record)
    {
        // adding Try catch here. In case some unhandled exception comes up, we don't fail the whole
        // request because of logging.
        try
        {
            $route = optional($this->app['router'])->currentRouteName();

            $bankingRoutes = Route::getBankingSpecificRoutes();

            if (in_array($route, $bankingRoutes, true) === false)
            {
                return;
            }

            $context = $record['context'] ?? null;

            if (empty($context) === true)
            {
                return;
            }

            $cardRegex = $this->app['config']['trace']['regex']['card_regex'];

            if (empty($cardRegex) === true)
            {
                $cardRegex = self::CARD_REGEX;
            }

            $emailRegex = $this->app['config']['trace']['regex']['email_regex'];

            if (empty($emailRegex) === true)
            {
                $emailRegex = self::EMAIL_REGEX;
            }

            $cvvRegex = $this->app['config']['trace']['regex']['cvv_regex'];

            if (empty($cvvRegex) === true)
            {
                $cvvRegex = self::CVV_REGEX;
            }

            $phoneNumberRegex = $this->app['config']['trace']['regex']['phone_number_regex'];

            if (empty($phoneNumberRegex) === true)
            {
                $phoneNumberRegex = self::PHONE_NUMBER_REGEX;
            }

            array_walk_recursive($context, function(& $item) use ($cardRegex, $emailRegex, $cvvRegex, $phoneNumberRegex) {
                if (is_string($item) === true)
                {
                    if ((strtolower($cardRegex) !== self::OFF) and
                        (preg_match_all($cardRegex, $item, $matches) !== false))
                    {
                        $matches = $matches[0];

                        foreach ($matches as $match)
                        {
                            $item = str_replace($match, 'CARD_NUMBER_SCRUBBED' . '(' . strlen($match) . ')', $item);
                        }
                    }
                    if ((strtolower($emailRegex) !== self::OFF) and
                        (preg_match_all($emailRegex, $item, $matches) !== false))
                    {
                        $matches = $matches[0];

                        foreach ($matches as $match)
                        {
                            $item = str_replace($match, self::EMAIL_SCRUBBED . '(' . strlen($match) . ')', $item);
                        }
                    }
                    if ((strtolower($cvvRegex) !== self::OFF) and
                        (preg_match_all($cvvRegex, $item, $matches) !== false))
                    {
                        $matches = $matches[0];

                        foreach ($matches as $match)
                        {
                            $item = str_replace($match, self::CVV_SCRUBBED . '(' . strlen($match) . ')', $item);
                        }
                    }
                    if ((strtolower($phoneNumberRegex) !== self::OFF) and
                        (preg_match_all($phoneNumberRegex, $item, $matches) !== false))
                    {
                        $matches = $matches[0];

                        foreach ($matches as $match)
                        {
                            $item = str_replace($match, self::PHONE_NUMBER_SCRUBBED . '(' . strlen($match) . ')', $item);
                        }
                    }
                }
            });
            $record['context'] = $context;
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException(
                $e,
                Logger::ERROR,
                TraceCode::SENSITIVE_BANKING_DETAILS_REDACTION_FAILURE_EXCEPTION
            );
        }
    }

    protected function keysBasedScrubbingForBankingRoutes(& $record)
    {
        try
        {
            $route = optional($this->app['router'])->currentRouteName();

            $bankingRoutes = Route::getBankingSpecificRoutes();

            if (in_array($route, $bankingRoutes, true) === true)
            {
                $record['context'] = $this->visitEachNode($record['context']);
            }
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException(
                $e,
                Logger::ERROR,
                TraceCode::KEYS_BASED_SCRUBBING_BANKING_INFO_FAILURE_EXCEPTION
            );
        }

    }

    protected function visitEachNode(& $context)
    {
        if (!empty($context))
        {
            foreach ($context as $key => &$value)
            {
                if (in_array($key, self::SENSITIVE_KEYS, true) === true)
                {
                    $this->scrubData($value);
                }
                if (is_array($value) === true)
                {
                    $this->visitEachNode($value);
                }
            }
        }

        return $context;
    }

    protected function scrubData(& $value)
    {
        if (is_array($value) === true)
        {
            foreach ($value as & $val)
            {
                $val = 'SCRUBBED' . '(' . strlen($val) . ')';
            }
        }
        else
        {
            if (is_string($value) === true)
            {
                $value = 'SCRUBBED' . '(' . strlen($value) . ')';
            }
        }
    }

    /**
     * So WebProcessor is pushed with request object available at the time as
     * part of framework's first set of things i.e. registering service
     * providers. After this http middleware are registered where
     * Fideloper\Proxy\TrustProxies (library) verifies and attaches x-forwarded-
     * headers of proxy server. That's it- tiny bad practice/miss causing issues.
     *
     * Refer: Razorpay\Trace\Processor\WebProcessor@getServerData.
     *
     * @param array &$record
     *
     * @return void
     */
    protected function overrideRequestAttributes(array &$record)
    {
        $record['request']['url'] = Tracing::maskUrl($this->app->request->getUri());
        $this->scrubData($record['request']['user_email']);
    }

    private function addTraceId(array &$record)
    {
        if ($this->app['basicauth']->isProxyOrPrivilegeAuth() === true)
        {
            $traceId = $this->app->request->headers->get(RequestHeader::X_REQUEST_TRACE_ID);

            $record['request']['x_request_trace_id'] = $traceId;
        }
    }

    // This function adds the Opentracing trace id(in our case jaeger id
    // The official docs says the global trace id is by default kept as
    // uber_trace_id. Following the same convention here.
    // Also this id will be added only in case routes are enabled for
    // distributed tracing
    private function addDistributedTraceId(array &$record)
    {
        if ((Tracing::isEnabled($this->app) === true) and
            (is_null(RouteFacade::current()) === false) and
            (Tracing::shouldTraceRoute(RouteFacade::current()) === true))
        {
            $distributedTraceId = Tracer::spanContext()->traceId();

            $record['request']['uber_trace_id'] =  $distributedTraceId;
        }
    }

    private function addAwsTraceId(array &$record)
    {
        $traceId = $this->app->request->headers->get(RequestHeader::X_AMAZON_TRACE_ID);

        $record['request']['aws_trace_id'] = $traceId;
    }

    private function addAwsTlsVersion(array &$record)
    {
        $tlsVersion = $this->app->request->headers->get(RequestHeader::X_AMAZON_TLS_VERSION);

        $record['request']['x-amzn-tls-version'] = $tlsVersion;
    }

    private function addRazorpayRequestId(array &$record)
    {
        $razorpayId = $this->app->request->headers->get(RequestHeader::X_RAZORPAY_REQUEST_ID);

        $record['request']['x-razorpay-request-id'] = $razorpayId;
    }

    protected function addTraceAttributesForWorkers(&$record)
    {
        /**@var Context $workerContext */
        $workerContext = $this->app['worker.ctx'];

        $uniqueJobId = $workerContext->fetchUniqueJobId();
        $jobUuid     = $workerContext->fetchJobUuid();

        // This will be useful for fetching logs for a single execution of a job
        if ($uniqueJobId !== null)
        {
            $record['request']['unique_job_id'] = $uniqueJobId;
        }

        // This will be useful for debugging if job is released multiple times by the queue.
        if ($jobUuid !== null)
        {
            $record['request']['job_uuid'] = $jobUuid;
        }
    }
}
