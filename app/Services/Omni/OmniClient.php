<?php

namespace App\Services\Omni;

use Auth;
use App;
use App\Merchant\Constants;
use App\User\Constants as UserConstants;
use Config;
use Request;
use Session;
use App\Trace\TraceCode;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Errors\ErrorCode;
use Illuminate\Foundation\Application;
use Razorpay\Api\Errors\BadRequestError;


class OmniClient
{

    protected $trace;

    /**
     * @var Application
     */
    protected $app;

    protected $options;

    protected $config;

    const CONTENT_TYPE = 'Content-Type';

    const APPLICATION_JSON = 'application/json';

    const HEADER_DASHBOARD_USER_ROLE = "X-Dashboard-User-Role";

    const HEADER_DASHBOARD_USER_ID = "X-Dashboard-User-Id";

    const HEADER_DASHBOARD_USER_EMAIL = "X-Dashboard-User-Email";

    const RAZORPAY_ACCOUNT_HEADER       = 'X-Razorpay-Account';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];

        $this->config = Config::get('app.omni');

        $this->options = [];
    }

    public function fetchPosPaymentsDevices(): array
    {
        return $this->execute(Constants::EZETAP_FETCH_POS_DEVICES_ENDPOINT, "get");
    }

    public function updateDeviceSettings($deviceId, $input): array
    {
        $url = sprintf(Constants::EZETAP_FETCH_POS_DEVICE_SETTINGS_ENDPOINT, $deviceId);
        return $this->execute($url, "post", $input);
    }

    private function execute($endPoint, $method, $input = null): array
    {
        try
        {
            $client = new Guzzle();

            $url = $this->config['url'] . $endPoint;

            $this->processHeaders();

            $this->processAuth();

            $this->options['timeout'] = $this->config['timeout'];

            if($method != 'get' && isset($input) && $input != null)
            {
                $this->options['json'] = $input;

                $headers = $this->options['headers'];

                $headers[self::CONTENT_TYPE] = self::APPLICATION_JSON;
            }

            $response   = $client->$method($url, $this->options);

            $statusCode = $response->getStatusCode();

            $body = json_decode($response->getBody(), true);

            // Process the response based on the HTTP status code
            if (($statusCode === 200))
            {
                // Successful response
                return [null, $body];
            }

            $this->trace->info(TraceCode::EZETAP_API_RESPONSE, [
                'response'        => $body
            ]);

            return [['Internal error occurred'], null];
        }
        catch (\Exception $e)
        {
            // Handle any exceptions that occurred during the request
            return [[$e->getMessage()], null];
        }
        catch (GuzzleException $e)
        {
            return [[$e->getMessage()], null];
        }
    }

    private function processHeaders()
    {
        $headers = [];

        $user = Auth::guard('user')->user();

        if (empty($user) === false) {
            $currentMerchant = $user->currentMerchant();

            if (empty($currentMerchant) === true) {
                throw new BadRequestError(
                    'Invalid merchant request.',
                    \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                    400);
            }

            $twoFaVerified = Session::get(UserConstants::TWO_FA_VERIFIED, false);

            $headers[self::HEADER_DASHBOARD_USER_ROLE] = $currentMerchant->role;

            $headers[self::HEADER_DASHBOARD_USER_ID] = $user->id;

            $headers[self::HEADER_DASHBOARD_USER_EMAIL] = $user->email;

            // Only string can be sent in http headers
            // bool value is converted to '1' for true & '0' for false
            $headers['X-Dashboard-User-2FA-Verified'] =
                $twoFaVerified ? 'true' : 'false';
        }
        else
        {
            if (app('request.ctx')->isOauthRequest() === true)
            {
                $userId = app('request.ctx')->getUserId();

                $headers[self::HEADER_DASHBOARD_USER_ID] = $userId;
            }
        }

        $accountId = Request::header(self::RAZORPAY_ACCOUNT_HEADER);

        if ($accountId)
        {
            $headers[self::RAZORPAY_ACCOUNT_HEADER] = $accountId;
        }

        $this->options['headers'] = $headers;
    }

    private function processAuth()
    {
        $user = Auth::guard('user')->user();

        if (empty($user) === false)
        {
            $currentMerchant = $user->currentMerchant();

            if (empty($currentMerchant) === true)
            {
                throw new BadRequestError(
                    'Invalid merchant request.',
                    \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                    400);
            }

        }

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $mid = app('request.ctx')->getMerchantId();
        }

        if (empty($currentMerchant) === true && empty($mid) === true)
        {
            throw new BadRequestError(
                'Invalid merchant request.',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400);
        }

        $currentMerchantId = $currentMerchant->id ?? $mid;

        $pass = $this->config['secret'];

        $auth = [
            'rzp_live_' . $currentMerchantId,
            $pass
        ];

        $this->options['auth'] = $auth;
    }
}
