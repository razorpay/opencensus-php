<?php

namespace Models\Admin;

use Config;
use Input;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Post\PostFile;

use Razorpay\Api\Request as ApiRequest;

// This is the default class we use for making requests
use RZP\Api as Api;

class RawApiRequest
{
    /**
     * Guzzle Client instance
     * @var Guzzle
     */
    protected $client;

    protected $params = [
        'timeout'   =>  15
    ];

    /**
     * Construct a RawApiRequest instance
     * @param $auth type of auth (proxy|admin)
     * @param $path relative path of the request
     */
    function __construct($input, $path)
    {
        // Create the guzzle client
        $this->client = new Guzzle([
            'base_url' => Config::get('api.url'),
            // We already have a few headers initialized for this class
            // including the X-Dashboard and Razorpay-API Header
            'headers'   =>  ApiRequest::getHeaders() + [
                'X-Dashboard' => 'true',
                'User-Agent'  => 'Razorpay-PHP/guzzle6'
            ]
        ]);

        $this->setupCredentials($input);
        $this->input = $input;
        $this->path = $path;
    }

    protected function setupCredentials($input)
    {
        // Setup credentials based on auth
        switch ($input['auth'])
        {
            case 'proxy':
                $this->setApiCredentials($input['merchant_id'], $input['mode']);
                break;

            case 'admin':
                $this->setApiCredentials('', $input['mode']);
                break;
        }
    }

    protected function setApiCredentials($merchantId, $mode)
    {
        $id = 'rzp_'.$mode;

        // id becomes rzp_{test|live}_merchant_id
        if ($merchantId)
        {
            $id = $id.'_'.$merchant_id;
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
        // The content type header might be missing and in those cases
        // We let guzzle figure it out.
        $this->params['headers']['Content-Type'] = Input::get('content_type', $default);
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
            // @note: The second parameter is crucial and a huge
            // security risk if not added
            mb_parse_str($this->input['body'], $postArray);
            $this->params['body'] = [];

            foreach ($postArray as $key => $value)
            {
                $this->params['body'][$key] = $value;
            }

            $file = Input::file('file');

            // Now that we have added all POST params, we add the file itself
            $fileFieldName = $this->input['file_name'];
            $postFile = new PostFile($file->getClientOriginalName(), fopen($file, 'r'));
            $this->params['body'][$fileFieldName] = $postFile;

        }
        // We just pass the body as it is
        else
        {
            // Uses one if available in the input, else defaults to this
            $this->setContentType('application/x-www-form-urlencoded');
            $this->params['body'] = Input::get('body', '');
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

        }
        // This captures all the errors that might happen for now
        catch(\GuzzleHttp\Exception\ConnectException $e)
        {
            $errors = ["Error in connecting to API"];
        }
        catch(\GuzzleHttp\Exception\GuzzleException $e)
        {
            $json = $e->getResponse()->json();
            $errors = [$json->error->description, "Status Code: {$e->getResponse()->getStatusCode()}"];
        }
        finally
        {
            return [$errors, $response];
        }
    }
}
