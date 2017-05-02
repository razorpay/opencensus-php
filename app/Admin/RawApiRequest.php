<?php

namespace App\Admin;

use Config;
use Input;
use Auth;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Post\PostFile;

use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors as RZPErrors;

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

            $this->path .= '?' . http_build_query($queryParams);
        }
    }

    protected function setupCredentials($input)
    {
        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            $adminToken = $adminUser->token;
        }

        // Setup credentials based on auth
        switch ($input['auth'])
        {
            case 'proxy':
                $merchantId = $input['merchant_id'] ?? null;

                if (empty($merchantId))
                {
                    $merchantId = Auth::guard('user')->user()->currentMerchant()->id;
                }

                $this->setApiCredentials($input['mode'], $merchantId);
                break;

            case 'admin_proxy':
                if (isset($adminToken) === true)
                {
                    $this->params['headers']['X-Admin-Token'] = $adminToken;
                }

                $merchantId = $input['merchant_id'] ?? null;

                if (empty($merchantId))
                {
                    $merchantId = Auth::guard('user')->user()->currentMerchant()->id;
                }

                $this->setApiCredentials($input['mode'], $merchantId);
                break;

            case 'admin':
                $this->setAdminCredentials($adminToken, $input['mode']);
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

    protected function setAdminCredentials($token, $mode = 'live')
    {
        $this->setApiCredentials($mode);

        $this->params['headers']['X-Admin-Token'] = $token;
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
            json_decode($this->params['body']);
            if (json_last_error() !== JSON_ERROR_NONE) {
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
        $postArray = [];
        // @note: The second parameter is crucial and a huge
        // security risk if not added because otherwise it
        // replicates register_globals
        mb_parse_str(Input::get('body', ''), $postArray);
        $body = [];

        foreach ($postArray as $key => $value)
        {
            $body[$key] = $value;
        }

        return $body;
    }

    /**
     * Sets the body and content type of the request as
     * per the guzzle input format
     * @return null
     */
    protected function prepareRequest()
    {
        // If we need to add the file to the body
        if ($this->input['file'] instanceof \SplFileInfo)
        {
            $file = Input::file('file');

            $this->params['body'] = $this->parseBody();

            // Now that we have added all POST params, we add the file itself
            // This contains the field name to be used for the file field
            $fileFieldName = $this->input['file_name'];
            // This contains the original file name with extension
            $fileName = $file->getClientOriginalName();

            // This is as per guzzle 5, will need to get changed for 6
            $postFile = new PostFile($fileFieldName, fopen($file, 'r'), $fileName);

            $this->params['body'][$fileFieldName] = $postFile;
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
     * @return Array standard response
     */
    public function send()
    {
        $errors = [];
        $response = null;

        try
        {
            $this->prepareRequest();

            $method = $this->input['method'];

            $response = $this->client->$method($this->path, $this->params)->json();

            return [null, $response];
        }
        // This captures all the errors that might happen for now
        catch(\GuzzleHttp\Exception\ConnectException $e)
        {
            $errors = ["Error in connecting to API"];
        }
        catch(\GuzzleHttp\Exception\GuzzleException $e)
        {
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
            $errors = [$e->getMessage()];
        }
        catch(RZPErrors\Error $e)
        {
            $errors = [$e->getMessage()];
        }

        return [$errors, null];
    }
}
