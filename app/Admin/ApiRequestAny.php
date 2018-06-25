<?php

namespace App\Admin;

use Auth;
use Input;
use Route;
use Trace;
use Config;
use Request;

use App\Http\ApiUrl;
use App\Trace\TraceCode;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Errors as RZPErrors;
use App\Merchant\Service as MerchantService;

class ApiRequestAny
{
    /**
     * Guzzle Client instance
     * @var Guzzle
     */
    protected $client;

    protected $mode;

    protected $path;

    protected $routeMap;

    protected $shouldProcessInput = true;

    const RAZORPAY_ACCOUNT_HEADER = 'X-Razorpay-Account';

    const CONTENT_TYPE_JSON = 'application/json';

    const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    const CONTENT_TYPE_MULTIPART_PREFIX = 'multipart/form-data;';

    /**
     * Construct a RawApiRequest instance
     *
     * @param string $mode live|test
     * @param string $base_url base url of dashboard
     */
    function __construct(array $options = [])
    {
        // Increase the time limit
        set_time_limit(600);

        // === Mode

        $this->mode = $options['mode'] ?? 'live';

        // === Client Type

        if (empty($options['client_type']) === true)
        {
            $routeName = Route::currentRouteName();

            if (in_array($routeName, ['merchant', 'admin'], true) === false)
            {
                // Default
                $this->clientType = 'user';
            }
            else
            {
                $this->clientType = $routeName;
            }
        }
        else
        {
            $this->clientType = $options['client_type'];
        }

        // === Headers

        $domain = \Request::server('SERVER_NAME');

        $defaultHeaders = [
            'X-Dashboard'       => 'true',
            'X-User-Agent'      => Request::header('User-Agent'),
            'X-IP-Address'      => Request::ip(),
            'X-Org-Hostname'    => $domain,
        ];

        $headers = $options['headers'] ?? [];

        $headers = array_merge($defaultHeaders, $headers);

        // === Request options

        $this->options = [
            'headers' => $headers,
        ];

        // === Guzzle client

        $this->client = new Guzzle([
            'base_url' => ApiUrl::getApiBaseUrl(),
        ]);

        // === Get API Route map config

        $this->routeMap = Config::get('api-route-map');

        // === Process client specific headers

        $this->processAuthHeaders();

        // === Forward cookies from the api

        $this->forwardCookies();

        // === Auto process input

        $processInput = $options['process_input'] ?? true;

        $useCustomFileKeys = $options['custom_file_keys'] ?? false;

        if ($processInput === true)
        {
            $this->processInput(null, $useCustomFileKeys);
        }
    }

    public function processAuthHeaders() {

        $clientType = $this->clientType;

        $baUser = null;

        if (empty($clientType) === false) {

            if ($clientType === 'merchant')
            {
                $user = Auth::guard('user')->user();

                if (empty($user) === false)
                {
                    $currentMerchant = $user->currentMerchant();

                    $this->options['headers']['X-Dashboard-User-Role'] = $currentMerchant->role;

                    $this->options['headers']['X-Dashboard-User-Id'] = $user->id;

                    $this->options['headers']['X-Dashboard-User-Email'] = $user->email;
                }

                $accountId = Request::header(self::RAZORPAY_ACCOUNT_HEADER);

                if ($accountId)
                {
                    $this->options['headers'][self::RAZORPAY_ACCOUNT_HEADER] = $accountId;
                }

                $baUser = $this->mode . '_' . $currentMerchant->id;

                $pass = Config::get('api.auth_pass');
            }
            else if ($clientType === 'admin')
            {
                $adminUser = Auth::guard('api')->user();

                if (empty($adminUser) === true)
                {
                    throw new \Razorpay\Api\Errors\BadRequestError(
                        'Invalid admin request.',
                        \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                        400);
                }

                $adminUsername = $adminUser->username ?? null;

                $this->options['headers']['X-Dashboard-Admin-Username'] = $adminUsername;

                $adminEmail = $adminUser->email ?? null;

                $this->options['headers']['X-Dashboard-Admin-Email'] = $adminEmail;

                $this->options['headers']['X-Admin-Token'] = $adminUser->token;

                $accountId = Request::header(self::RAZORPAY_ACCOUNT_HEADER);

                if ($accountId)
                {
                    $this->options['headers'][self::RAZORPAY_ACCOUNT_HEADER] = $accountId;
                }

                $baUser = $this->mode;

                $pass = Config::get('api.auth_pass');
            }
            else if ($clientType === 'user')
            {
                // NOTE: Merchant will be able to access the user/guest routes

                $user = Auth::guard('user')->user();

                if (empty($user) === false)
                {
                    $this->options['headers']['X-Dashboard-User-Id'] = $user->id;

                    $this->options['headers']['X-Dashboard-User-Id'] = $user->id;

                    $this->options['headers']['X-Dashboard-User-Email'] = $user->email;
                }

                // NOTE: We should NEVER hit this as Dashboard internal.
                $baUser = 'live';

                $pass = Config::get('api.auth_guest_pass');
            }
        }

        // Set BasicAuth creds
        if (empty($baUser) === false)
        {
            $this->options['auth'] = [
                'rzp_' . $baUser,
                $pass
            ];
        }

        return $this;
    }

    // process body according to content-type
    public function processInput($data = null, $useCustomFileKeys = false)
    {
        $input = $data ?? Request::all();

        $defaultContentType = self::CONTENT_TYPE_JSON;

        $contentType = Request::header('content_type', $defaultContentType);

        // Laravel is not considering empty string('') as empty header in Request::header
        if (empty($contentType) === true)
        {
            $contentType = $defaultContentType;
        }

        // auth check just for precaution, so that guests do not upload files
        if (strpos($contentType, self::CONTENT_TYPE_MULTIPART_PREFIX) === 0)
        {
            foreach ($input as $key => $val)
            {
                if (is_array($val))
                {
                    $input = $this->flatten($input, $val, $key);

                    unset($input[$key]);
                }
            }

            foreach ($input as $key => $val)
            {
                if ($val instanceof \SplFileInfo)
                {
                    $fileName = $val->getClientOriginalName();

                    // used in case of file upload through epos
                    if ($useCustomFileKeys === true)
                    {
                        $oldKey = $key;

                        $key = MerchantService::UPLOAD_KEYS[$key];

                        unset($input[$oldKey]);
                    }

                    $input[$key] = new PostFile($key, fopen($val, 'r'), $fileName);
                }
            }
        }

        if ($contentType === self::CONTENT_TYPE_JSON)
        {
            $this->options['json'] = $input;
        }
        else
        {
            $this->options['body'] = $input;
        }

        return $this;
    }

    /**
     * Fires the request to the API
     * @return array standard response
     */
    public function send($path, $method = null)
    {
        $exception = null;
        $errors = [];
        $response = null;
        $method = $method ?? Request::method();

        try
        {
            $response = $this->client
                             ->$method($path, $this->options)
                             ->json();

            return [null, $response];
        }
        // This captures all the errors that might happen for now
        catch(\GuzzleHttp\Exception\ConnectException $e)
        {
            $exception = $e;
            $errors = ["Error in connecting to API"];
        }
        catch(\GuzzleHttp\Exception\GuzzleException $e)
        {
            $exception = $e;
            $json = $e->getResponse()->json();
            $errors = [$json['error']['description'], "Status Code: {$e->getResponse()->getStatusCode()}"];
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            $json = $e->getResponse()->json();
            $errors = [$json['error']['description'], "Status Code: {$e->getResponse()->getStatusCode()}"];
        }
        catch(\GuzzleHttp\Exception\ServerException $e)
        {
            $exception = $e;
            $errors = [$e->getMessage()];
        }
        catch(RZPErrors\Error $e)
        {
            $exception = $e;
            $errors = [$e->getMessage()];
        }

        // Logs non-client side exceptions.
        // Use case: Request didn't reach API, or failed with 5xx before API made
        // a log of it. In such case we don't know what happened. Dashboard as a
        // client should at least log for all server errors received from API.
        if ($exception !== null)
        {
            Trace::error(
                TraceCode::API_REQUEST_FAILURE,
                [
                    'message' => $e->getMessage(),
                ]);
        }

        return [$errors, null];
    }

    protected function flatten($parent, $array, $prefix)
    {

        foreach ($array as $key => $value) {
            if (is_array($value))
            {
                $parent = $this->flatten($parent, $value, $prefix.'['.$key.']');
            }
            else
            {
                $parent[$prefix.'['.$key.']'] = $value;
            }
        }

        return $parent;
    }

    public function forwardCookies()
    {
        // UTM cookies needs forwarding with some manipulation because PHP cookies only accept ASCII
        if (empty($_COOKIE['rzp_utm']) === false)
        {
            $cookie = $_COOKIE['rzp_utm'];

            $cookie = str_replace('+', '%2B', $cookie);

            $this->options['headers']['Cookie'] = 'rzp_utm=' . $cookie;
        }
    }
}
