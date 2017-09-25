<?php

namespace App\Admin;

use Config;
use Input;
use Auth;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Post\PostFile;

use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors as RZPErrors;
use Trace;
use App\Trace\TraceCode;

// This is the default class we use for making requests
use App\RZP\Api as Api;

use Request;

class RawApiRequest
{
    /**
     * Guzzle Client instance
     * @var Guzzle
     */
    protected $client;

    protected $params = [
        'timeout'   =>  60
    ];

    const RAZORPAY_ACCOUNT_HEADER = 'X-Razorpay-Account';

    /**
     * Construct a RawApiRequest instance
     * @param array $auth of auth (proxy|admin)
     * @param string $path relative path of the request
     */
    function __construct($input, $path, $autoBuildQuery = true)
    {
        // Increase the time limit
        set_time_limit(600);

        $options = [
            'base_url' => Config::get('api.url'),
            // We already have a few headers initialized for this class
            // including the X-Dashboard and Razorpay-API Header
            'defaults' => [
                'headers'   =>  ApiRequest::getHeaders() + [
                    'X-Dashboard'   => 'true',
                    'X-User-Agent'  => Request::header('User-Agent'),
                    'X-IP-Address'  => Request::ip(),
                ]
            ]
        ];

        // Create the guzzle client
        $this->client = new Guzzle($options);

        $this->setupCredentials($input);
        $this->input = $input;
        $this->path = $path;

        if (!empty(Request::query()) and $autoBuildQuery)
        {
            $this->path .= '?' . http_build_query(Request::query());
        }
        else if (!empty(Request::query('query_params')))
        {
            $queryParams = json_decode(Request::query('query_params'), true);

            if (is_array($queryParams))
            {
                $this->path .= '?' . http_build_query($queryParams);
            }
        }
        // This block is supposed to handle internal generic calls
        // not the ones coming from frontend/xhr.
        else if (isset($input['query_params']) && !empty($input['query_params']))
        {
            $queryParams = $input['query_params'];

            if (is_array($queryParams))
            {
                $this->path .= '?' . http_build_query($queryParams);
            }
        }
    }

    protected function setupCredentials($input)
    {
        $adminUser = Auth::guard('api')->user();

        $adminToken = null;

        if (empty($adminUser) === false)
        {
            $adminToken = $adminUser->token;
        }

        $merchantId = $this->resolveMerchantId($input, $adminToken);

        if (isset($merchantId) === true) {
            /**
             * Setting X-Razorpay-Account header in case of market place routes.
             * The handling of this header is already taken care in api
             */
            $accountId = $input['account_id'] ?? null;

            if (empty($accountId) === false) {
                $this->params['headers'][self::RAZORPAY_ACCOUNT_HEADER] = $accountId;
            }
        }

        // Setup credentials based on auth
        switch ($input['auth'])
        {
            case 'proxy':
                $this->setApiCredentials($input['mode'], $merchantId);
                break;

            case 'admin_proxy':
                if (isset($adminToken) === true)
                {
                    $this->params['headers']['X-Admin-Token'] = $adminToken;
                }

                $this->setApiCredentials($input['mode'], $merchantId);
                break;

            case 'admin':
                $this->setAdminCredentials($adminToken, $input['mode'], $merchantId);
                break;

            case 'internal':
                $this->setApiCredentials($input['mode']);
                break;

            case 'admin_internal':
                if (isset($adminToken) === true)
                {
                    $this->params['headers']['X-Admin-Token'] = $adminToken;
                }

                $this->setApiCredentials($input['mode']);
                break;
        }
    }

    protected function setAdminCredentials($token, $mode = 'live', $merchantId = null)
    {
        $this->setApiCredentials($mode);

        if (empty($token) === true)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                'Admin token invalid.',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $this->params['headers']['X-Admin-Token'] = $token;

        if (empty($merchantId) === false) {

            $this->params['headers'][self::RAZORPAY_ACCOUNT_HEADER] = $merchantId;
        }
    }

    protected function setApiCredentials($mode, $merchantId = '')
    {
        $id = 'rzp_'.$mode;

        // id becomes rzp_{test|live}_merchant_id
        if ($merchantId)
        {
            $id = $id.'_'.$merchantId;
        }

        $secret = Config::get('api.auth_pass');

        $this->params['auth'] = [$id, $secret];
    }

    /**
     * Gets the content type for the request we are making. This method is only
     * called if the upload does not include a file
     * @return string
     */
    protected function setContentType($default = 'application/x-www-form-urlencoded')
    {
        $contentType = Input::get('content_type', $default);

        if ($contentType === "application/json")
        {
            // To check if the the content is already a JSON, we decode the content
            // and check for any JSON error. If no error then it is already a valid JSON
            // and there is no need to do a json_encode
            $bodyIsArray = is_array($this->params['body']);

            if ($bodyIsArray === false)
            {
                json_decode($this->params['body']);
            }

            if ($bodyIsArray or json_last_error() !== JSON_ERROR_NONE)
            {
                $this->params['body'] = json_encode($this->params['body']);
            }
        }

        // The content type header might be missing and in those cases
        // We let guzzle figure it out.
        $this->params['headers']['Content-Type'] = $contentType;
    }

    /**
     * This only gets called if it's a multipart file upload
     */
    protected function parseBody()
    {
        $inputBody = $this->input['body'] ?? Input::get('body', '');

        if (is_array($inputBody)) {
            return $inputBody;
        }

        $postArray = [];

        // @note: The second parameter is crucial and a huge
        // security risk if not added because otherwise it
        // replicates register_globals
        mb_parse_str($inputBody, $postArray);

        $body = [];

        foreach ($postArray as $key => $value)
        {
            $body[$key] = $value;
        }

        return $body;
    }

    protected function prepareBodyFromFileInput($fileFieldName, $file)
    {
        if ($file instanceof \SplFileInfo)
        {
            // This contains the original file name with extension
            $fileName = $file->getClientOriginalName();

            if (empty($file->getFileName()) === true)
            {
                throw new \Razorpay\Api\Errors\BadRequestError(
                    'Filename cannot be empty',
                    \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                    400
                );
            }

            // This is as per guzzle 5, will need to get changed for 6
            $postFile = new PostFile($fileFieldName, fopen($file, 'r'), $fileName);

            $this->params['body'][$fileFieldName] = $postFile;
        }
    }

    /**
     * Sets the body and content type of the request as
     * per the guzzle input format
     * @return null
     */
    protected function prepareRequest()
    {
        // If we need to add the file to the body
        if (is_array($this->input['file']) === true or $this->input['file'] instanceof \SplFileInfo)
        {
            // Incase the input contains an array of files
            if (is_array($this->input['file']) === true)
            {
                $this->params['body'] = $this->parseBody();

                $files = $this->input['file'];

                foreach ($files as $fileFieldName => $file)
                {
                    $this->prepareBodyFromFileInput($fileFieldName, $file);
                }
            }
            else
            {
                $file = $this->input['file'];

                $this->params['body'] = $this->parseBody();

                // Now that we have added all POST params, we add the file itself
                // This contains the field name to be used for the file field
                $fileFieldName = $this->input['file_name'];

                $this->prepareBodyFromFileInput($fileFieldName, $file);
            }
        }
        // We just pass the body as it is
        else
        {
            // Setting the body before the content type is important.
            // Why? Check setContentType function
            $this->params['body'] = $this->input['body'] ?? Input::get('body', '');

            $this->setContentType();
        }
    }

    /**
     * Fires the request to the API
     * @return array standard response
     */
    public function send()
    {
        $exception = null;
        $errors = [];
        $response = null;

        try
        {
            $this->prepareRequest();

            $method = $this->input['method'];

            $response = $this->client
                             ->$method($this->path, $this->params)
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

    /**
     * function to resolve merchantId from input/Auth guard
     * @param  array  $input
     *
     * @return string
     */
    protected function resolveMerchantId($input, $adminToken = null)
    {
        $merchantId = null;

        $merchantUser = Auth::guard('user')->user();

        // If current user is NOT an admin
        // Due to login as merchant this has to be in this way!

        if (empty($adminToken) === false)
        {
            $merchantId = $input['merchant_id'] ?? null;
        }

        if (empty($merchantId) === true and empty($merchantUser) === false)
        {
            $currentMerchant = $merchantUser->currentMerchant();

            if (empty($currentMerchant) === false)
            {
                $merchantId = $currentMerchant->id;
            }
        }

        return $merchantId;
    }
}
