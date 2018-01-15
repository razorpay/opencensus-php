<?php

namespace App\Admin;

use Config;
use Input;
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

    const RAZORPAY_ACCOUNT_HEADER = 'X-Razorpay-Account';
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';
    const CONTENT_TYPE_MULTIPART_PREFIX = 'multipart/form-data;';

    /**
     * Construct a RawApiRequest instance
     * @param array $auth of auth (proxy|admin)
     * @param string $path relative path of the request
     */
    function __construct()
    {
        // Increase the time limit
        set_time_limit(600);

        $options = [
            'base_url' => Config::get('api.url')
        ];

        // Create the guzzle client
        $this->client = new Guzzle($options);
    }

    /**
     * makes raw api calls with X-Admin-Token added to header
     */
    public function sendWithAdminToken($auth, $path)
    {
        $adminUser = Auth::guard('api')->user();

        $options = [
            'headers' => [
                'X-Admin-Token' => $adminUser->token,
                'X-Org-Id' => $adminUser->org_id
            ]
        ];

        return $this->send($path, $options, $auth);
    }

    public function sendWithMerchantProxy($mode, $path)
    {
        $merchantUser = Auth::guard('user')->user();

        $currentMerchant = $merchantUser->currentMerchant();


        if (empty($currentMerchant) === false)
        {
            $merchantId = $currentMerchant->id;

            $options = [
                'headers' => [
                    'X-Merchant-Role' => $currentMerchant->role
                ]
            ];

            $user = $mode.'_'.$merchantId;

            return $this->send($path, $options, $user);
        }
    }

    /**
     * makes raw api calls with X-Dashboard-User-Id added to header
     */
    public function sendWithUserId($path)
    {
        $user = Auth::guard('user')->user();

        $options = [
            'headers' => [
                'X-Dashboard-User-Id' => $user->id
            ]
        ];

        return $this->send($path, $options);
    }

    /**
     * Fires the request to the API
     * @return array standard response
     */
    public function send($path, $options = [], $auth = null)
    {
        $exception = null;
        $errors = [];
        $response = null;

        $method = Request::method();

        $options = $this->processOptions($auth, $options);

        try
        {
            $response = $this->client
                             ->$method($path, $options)
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

    // set Content-Type header
    // and process body according to content-type
    public function processOptions($auth, $options)
    {

        if ($auth) {
            $options['auth'] = [
                'rzp_'.$auth,
                Config::get('api.auth_pass')
            ];
        }

        $input = Request::all();

        $headers = $options['headers'] ?? [];

        $contentType = Request::header('content-type', self::CONTENT_TYPE_JSON);

        // if contentType begins with
        // auth check just for precaution, so that guests do not upload files
        if ($auth && strpos($contentType, self::CONTENT_TYPE_MULTIPART_PREFIX) === 0)
        {
            // $options['multipart'] = $input;
            $this->processUploads($options);
        }
        else if ($contentType === self::CONTENT_TYPE_JSON)
        {
            $options['json'] = $input;
        }
        else if ($contentType === self::CONTENT_TYPE_FORM)
        {
            $options['form_params'] = $input;
        }
        else
        {
            $options['body'] = $input;
        }

        $defaultHeaders = [
            'X-Dashboard'   => 'true',
            'X-User-Agent'  => Request::header('User-Agent'),
            'X-IP-Address'  => Request::ip()
        ];


        $options['headers'] = $defaultHeaders + $headers;

        return $options;

    }
}
