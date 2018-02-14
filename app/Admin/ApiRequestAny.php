<?php

namespace App\Admin;

use Config;
use Input;
use Route;
use Auth;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Post\PostFile;
use Razorpay\Api\Errors as RZPErrors;
use Trace;
use App\Trace\TraceCode;

// This is the default class we use for making requests
use App\RZP\Api as Api;

use Request;

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
    function __construct($mode, $routeName = null, $shouldProcessInput = true)
    {
        // Increase the time limit
        set_time_limit(600);

        $this->mode = $mode;

        $this->routeName = $routeName;

        $domain = \Request::server('SERVER_NAME');

        $this->options = [
            'headers' => [
                'X-Dashboard'       => 'true',
                'X-User-Agent'      => Request::header('User-Agent'),
                'X-IP-Address'      => Request::ip(),
                'X-Org-Hostname'    => $domain,
            ],
        ];

        $this->processRoute($mode);

        $this->shouldProcessInput = $shouldProcessInput;

        if ($this->shouldProcessInput === true)
        {
            $this->processInput();
        }

        $base_url = Config::get('api.url');

        // Create the guzzle client
        $this->client = new Guzzle([
            'base_url' => $base_url
        ]);

        $this->routeMap = Config::get('api-route-map');
    }

    public function processRoute($mode) {

        $routeName = $this->routeName ?? Route::currentRouteName();

        if (empty($routeName) === false) {

            if ($routeName === 'merchant')
            {
                $currentMerchant = Auth::guard('user')->user()->currentMerchant();

                $this->options['headers']['X-Dashboard-User-Role'] = $currentMerchant->role;

                $mode .= '_' . $currentMerchant->id;

                $pass = Config::get('api.auth_pass');
            }
            else if ($routeName === 'admin')
            {
                $adminUser = Auth::guard('api')->user();

                $this->options['headers']['X-Admin-Token'] = $adminUser->token;

                $pass = Config::get('api.auth_pass');
            }
            else if ($routeName === 'user')
            {
                // NOTE: Merchant will be able to access the user/guest routes

                $user = Auth::guard('user')->user();

                if (empty($user) === false)
                {
                    $headers['X-Dashboard-User-Id'] = $user->id;
                }

                // NOTE: We should NEVER hit this as Dashboard internal.
                $mode = 'live';

                $pass = Config::get('api.auth_pass');
            }
        }

        // If we have a mode, set BasicAuth creds
        if (empty($mode) === false) {
            $this->options['auth'] = [
                'rzp_' . $mode,
                $pass
            ];
        }

        return $this;
    }

    // process body according to content-type
    public function processInput($data = null)
    {
        $input = $data ?? Request::all();

        $contentType = Request::header('content-type', self::CONTENT_TYPE_JSON);

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
}
