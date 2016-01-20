<?php

namespace Models\Admin;

use Input;

use GuzzleHttp\Client as Guzzle;
// This is the default class we use for making requests
use Razorpay\Api\Request as ApiRequest;


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
        $this->client = new Guzzle([
            'base_url' => ApiRequest::$baseUrl,
            'defaults' => [
                // We already have a few headers initialized for this class
                // including the X-Dashboard and Razorpay-API Header
                'headers'   =>  ApiRequest::$headers
            ]
        ]);

        $this->setupCredentials($input);
        $this->input = $input;
        $this->path = path;
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
                $this->setApiCredentials(null, $input['mode']);
                break;
        }
    }

    protected function setApiCredentials($user, $password)
    {
        $this->params['auth'] = [$user, $password];
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
        $this->params['header']['Content-Type'] = Input::get('content_type', $default);
    }

    /**
     * Sets the body and content type of the request as
     * per the guzzle input format
     * @return null
     */
    protected function prepareRequest()
    {
        // If we need to add the file to the body
        if ($input['file'] instanceof \SplFileInfo)
        {
            // @note: The second parameter is crucial and a huge
            // security risk if not added
            mb_parse_str($input['body'], $postArray);
            $this->params['multipart'] = [];

            foreach ($postArray as $key => $value)
            {
                $this->params['multipart'][] = [
                    'name'      =>  $key,
                    'contents'  =>  $value
                ];
            }

            $file = Input::file('file');

            // Now that we have added all POST params, we add the file itself
            $this->params['multipart'][] = [
                'name'      =>  $this->input['file_name'],
                'contents'  =>  $file,
                'filename'  =>  $file->getClientOriginalName(),
            ];
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
        $this->prepareRequest();
        $errors = [];
        $response = null;

        try
        {
            $request = $client->createRequest($this->input['method'], $this->path, $this->params);
            \Trace::info('TRACE_RAW_API_REQUEST', $this->params);
            $response = $request->send()->json();
        }
        // This captures all the errors that might happen for now
        catch(\GuzzleHttp\Exception\GuzzleException $e)
        {
            $json = json_decode($e->response);
            $errors = [$json['error']['description'], "Status Code: {$e->response->getStatusCode()}"];
        }
        finally
        {
            return [$errors, $response];
        }
    }
}
