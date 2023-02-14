<?php

namespace RZP\Services;

use App;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Http\Request\Requests;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Account;
use RZP\Exception\ServerErrorException;


class Raven
{
    const SMS_ID          = 'sms_id';
    const OTP             = 'otp';
    const EXPIRES_AT      = 'expires_at';

    const REQUEST_TIMEOUT = 60;

    const TEST_SMS_ID     = '10000000000sms';
    // If raven service is mock, this OTP only is evaluated as true in verify.
    const MOCK_VALID_OTPS = array('0007', '000007');

    // In test mode this otp is evaluated as true in verify.
    const TEST_VALID_OTP = '754081';

    // To add retries for raven request
    const MAX_ALLOWED_RAVEN_REQUEST_ATTEMPTS = 2;

    // Passing orgId into sms request raven service
    const RavenOrgIdSmsRequest = "raven_org_id_sms_request";

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    const RAVEN_URLS = [
        'send-sms'      => 'sms',
        'send-otp'      => 'sms/send-otp',
        'verify-otp'    => 'sms/verify-otp',
        'generate-otp'  => 'otp/generate',
    ];

    protected $validationErrors = [
        ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
    ];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.raven');

        $this->baseUrl = $this->config['url'];

        // Refer: https://github.com/razorpay/api/issues/6385
        $this->mode = (isset($app['rzp.mode']) === true) ? $app['rzp.mode'] : null;

        $this->key = 'rzp';

        $this->secret = $this->config['secret'];

        $this->proxy = $app['config']->get('gateway.proxy_address');
    }

    public function sendOtp(array $input): array
    {
        $app = App::getFacadeRoot();

        $response = null;

        $input = $this->appendOrgIdInContext($input);

        // We need SMS/OTP to work for Axis Env
        if (($app->environment(Environment::PRODUCTION) === false) and
            ($app->environment(Environment::AXIS) === false))
        {
            $response[self::SMS_ID] = self::TEST_SMS_ID;
        }
        else
        {
            $response = $this->sendRequest(self::RAVEN_URLS['send-otp'], 'post', $input);
        }

        return $response;
    }

    /**
     * @param array $input
     * @param bool  $mockInTestMode
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\RuntimeException
     * @throws \WpOrg\Requests\Exception
     */
    public function generateOtp(array $input, $mockInTestMode = true): array
    {
        try
        {
            if (($this->mode === Mode::TEST) and
                ($mockInTestMode === true))
            {
                return [
                    self::OTP        => self::MOCK_VALID_OTPS[0],
                    self::EXPIRES_AT => Carbon::now()->addMinutes(30)->timestamp,
                ];
            }

            $response = $this->sendRequest(self::RAVEN_URLS['generate-otp'], 'post', $input);

            // Note: this is a hack to prevent rare eventuality. Issue: Request wasn't (probably)
            // reaching Raven but here in API, we were receiving response with 200. For details refer
            // to the slack thread: https://razorpay.slack.com/archives/C012KKG1STS/p1617873425033100
            if (empty($response['otp']) === true)
            {
                $this->trace->info(TraceCode::RAVEN_INVALID_OTP_RESPONSE, compact('response'));

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_RESPONSE_OTP_GENERATE_RAVEN);
            }

            return $response;
        }
        catch (\Exception $e)
        {
            $dimensions = [
                Metric::LABEL_ROUTE => self::RAVEN_URLS['generate-otp'],
                Metric::LABEL_ACTION => $data['action'] ?? '',
                Metric::LABEL_MESSAGE => $e->getMessage() ?? '',
            ];

            $this->trace->count(Metric::RAVEN_REQUEST_FAILED, $dimensions);

            throw $e;
        }
    }

    /**
     * Makes call to raven service to send an SMS. By default if it's test mode
     * we return mock success response. But conditionally callee can specify
     * if it should not mock test mode behavior.
     *
     * @param array        $input
     * @param bool|boolean $mockInTestMode
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\RuntimeException
     * @throws \WpOrg\Requests\Exception
     */
    public function sendSms(array $input, bool $mockInTestMode = true): array
    {
        $input = $this->appendOrgIdInContext($input);

        if (($this->mode === Mode::TEST) and ($mockInTestMode === true))
        {
            return [self::SMS_ID => self::TEST_SMS_ID];
        }
        else
        {
            $response = $this->sendRequest(self::RAVEN_URLS['send-sms'], 'post', $input);
        }

        return $response;
    }

    public function verifyTestOtp(array $input): array
    {
        $otp = $input['otp'];

        $response = null;

        if ($this->mode === Mode::LIVE)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_IN_LIVE);
        }

        if ($otp !== self::TEST_VALID_OTP)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_OTP);
        }

        $response['success'] = true;

        return $response;
    }

    public function verifyOtp(array $input, bool $mock = false): array
    {
        try
        {
            $app = App::getFacadeRoot();

            $response = null;

        // We need SMS/OTP to work for Axis Env
        if (($app->environment(Environment::PRODUCTION) === false) and
            ($app->environment(Environment::AXIS) === false))
            {
                if (in_array($input['otp'], self::MOCK_VALID_OTPS) === false)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_OTP);
                }
                else
                {
                    $response['success'] = true;
                }
            }
            else
            {
                // If mock is true, don't send request to raven service
                if ($mock === true)
                {
                    return $this->verifyTestOtp($input);
                }

                $response = $this->sendRequest(self::RAVEN_URLS['verify-otp'], 'post', $input);
            }

            return $response;
        }
        catch (\Exception $e)
        {
            $dimensions = [
                Metric::LABEL_ROUTE => self::RAVEN_URLS['verify-otp'],
                Metric::LABEL_ACTION => $data['action'] ?? '',
                Metric::LABEL_MESSAGE => $e->getMessage() ?? ''
            ];

            $this->trace->count(Metric::RAVEN_REQUEST_FAILED, $dimensions);

            throw $e;
        }

    }

    public function smsCallback($gateway, $input)
    {
        $relativeUrl = 'callback/' . $gateway;

        $response = $this->sendRequest($relativeUrl, 'post', $input);

        return $response;
    }

    /**
     * @param      $url
     * @param      $method
     * @param null $data
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\RuntimeException
     * @throws \WpOrg\Requests\Exception
     */
    public function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
            // 'proxy' => $this->proxy
        );

        $request = array(
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendRavenRequest($request);

        $decodedResponse = json_decode($response->body, true);

        $decodedResponse = $decodedResponse ?? [];

        $this->traceResponse($decodedResponse);

        //check if $response is a valid json
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'External Operation Failed');
        }

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }

    protected function traceResponse(array $response)
    {
        unset($response['otp']);

        if (isset($response['receiver']) === true)
        {
            $response['receiver'] = mask_phone($response['receiver']);
        }

        $this->trace->info(TraceCode::RAVEN_RESPONSE, $response);
    }

    protected function sendRavenRequest($request, int $maxAllowedAttempts = self::MAX_ALLOWED_RAVEN_REQUEST_ATTEMPTS)
    {
        $this->traceRequest($request);

        $currentAttempt = 1;

        while ($currentAttempt <= $maxAllowedAttempts)
        {
            try
            {
                $response = $this->getRavenRequestResponse($request);

                if ($currentAttempt > 1)
                {
                    $this->trace->info(TraceCode::RAVEN_RETRY_SUCCESS, ['attempt' => $currentAttempt]);
                }

                return $response;
            }
            catch(\WpOrg\Requests\Exception $e)
            {
                if ($currentAttempt == $maxAllowedAttempts)
                {
                    throw new ServerErrorException(
                        "Failed to complete request",
                        ErrorCode::SERVER_ERROR_RAVEN_FAILURE,
                        ['maxAllowedAttempts' => $maxAllowedAttempts],
                        $e
                    );
                }
            }

            $currentAttempt++;
        }

        return $response;
    }

    protected function getRavenRequestResponse($request)
    {
        $method = $request['method'];

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['options']);
        }
        catch(\WpOrg\Requests\Exception $e)
        {
            throw $e;
        }

        return $response;
    }

    protected function traceRequest($request)
    {
        unset($request['options']['auth']);
        unset($request['content']['otp']);
        unset($request['content']['params']['otp']);

        if (isset($request['content']['receiver']) === true)
        {
            $request['content']['receiver'] = mask_phone($request['content']['receiver']);
        }

        $this->trace->info(TraceCode::RAVEN_REQUEST, $request);
    }

    protected function checkErrors($response)
    {
        if (isset($response['error']))
        {
            $errorCode = $response['error']['internal_error_code'];

            if (in_array($errorCode, $this->validationErrors, true))
            {
                throw new Exception\BadRequestValidationFailureException(
                    $response['error']['description']);
            }

            throw new Exception\BadRequestException($errorCode);
        }
    }

    /**
     * Appending org id in context in raven service call so that these details,
     * Can be used on stork service for differentiating SMS requests by Org ID.
     * @param array $input
     * @return array|mixed
     */
    protected function appendOrgIdInContext(array $input)
    {
        $app = App::getFacadeRoot();

        $basicAuth = $app['basicauth'];

        if (empty($input['stork']['context']['org_id']) === false)
        {
            return $input;
        }

        $orgId = $basicAuth->getOrgId() ?? '';

        if (empty($orgId) === false)
        {
            // check if RazorX experiment is turned on then only pass orgId in sms request raven
            if (strtolower(app('razorx')->getTreatment($orgId, self::RavenOrgIdSmsRequest, $this->mode ?? Mode::LIVE)) === 'on')
            {
                // By default app is appending org_ as prefix but stork service expecting without prefix so trimming
                $trimmedOrgId = str_replace('org_', '', $orgId);

                $input['stork']['context']['org_id'] = $trimmedOrgId;
            }

            return $input;
        }

        // Logging template name which doesn't have orgID.
        if (isset($input['template']))
        {
            $app['trace']->info(TraceCode::RAVEN_REQUEST_ORG_ID_EMPTY, [
                  "template" => $input['template'],
                ]);
        }

        // Adding MID in raven request
        if (empty($input['stork']['owner_id']) === false)
        {
            return $input;
        } else {
            $input['stork']['owner_id'] = $input['context']?? Account::TEST_ACCOUNT;
        }

        return $input;

    }
}
