<?php

namespace App\Admin;

use Auth;
use Input;
use Trace;
use Config;
use Session;
use Request;

use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\Trace\SpanTrace;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Errors as RZPErrors;
use Razorpay\Api\Request as ApiRequest;
use OpenCensus\Trace\Propagator\ArrayHeaders;


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
    function __construct($input, $path, $headers = [],$autoBuildQuery = true)
    {
        // Increase the time limit
        set_time_limit(600);

        $options = [
            'base_url' => ApiUrl::getApiBaseUrl(),
            // We already have a few headers initialized for this class
            // including the X-Dashboard and Razorpay-API Header
            'defaults' => [
                'headers'   =>  ApiRequest::getHeaders() + $headers + [
                        'X-Dashboard'                 => 'true',
                        'X-User-Agent'                => Request::header('User-Agent'),
                        'X-IP-Address'                => Request::ip(),
                        'X-Dashboard-User-Session-Id' => Session::getId(),
                ],
                'timeout' => Config::get('api.request_timeout'),
            ]
        ];

        $this->setUpCookies($input);

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

            $this->params['headers']['X-Admin-Token'] = $adminToken;
        }
        else
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                'Admin token not set.',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400);
        }

        $merchantId = $this->resolveMerchantId($input, $adminToken);

        if (isset($merchantId) === true) {
            /**
             * Setting X-Razorpay-Account header in case of market place routes.
             * The handling of this header is already taken care in api
             */
            $accountId = $input['account_id'] ?? null;

            if (empty($accountId) === false)
            {
                $this->params['headers'][self::RAZORPAY_ACCOUNT_HEADER] = $accountId;
            }
        }

        // Setup credentials based on auth
        switch ($input['auth'])
        {
            case 'proxy':
                $this->setApiCredentials($input['mode'], $merchantId);
                break;

            case 'internal':
                $this->setApiCredentials($input['mode']);

                // Admin should be able to hit any internal route
                // which means we have to *skip* sending admin token
                unset($this->params['headers']['X-Admin-Token']);
                break;

            case 'admin':
                $this->setApiCredentials($input['mode']);
                break;
        }
    }

    /**
     * Sets utm cookies for proxy routes.
     * @param array $input
     */
    protected function setupCookies(array $input)
    {
        if ($input['auth'] === 'proxy' and empty($_COOKIE['rzp_utm']) === false)
        {
            $cookie = $_COOKIE['rzp_utm'];

            $cookie = str_replace('+', '%2B', $cookie);

            $this->params['headers']['Cookie'] = 'rzp_utm=' . $cookie;
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

    protected function getFileBodyFromFileInput($fileFieldName, $file)
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

            return $postFile;
        }

        return null;
    }

    /**
     * Sets the body and content type of the request as
     * per the guzzle input format
     * @return null
     */
    protected function prepareRequest()
    {
        // If we need to add the file to the body
        if ((is_array($this->input['file']) === true) or ($this->input['file'] instanceof \SplFileInfo))
        {
            // Incase the input contains an array of files
            if (is_array($this->input['file']) === true)
            {
                $files = $this->input['file'];

                $this->params['body'] = array_merge($this->parseBody(), $this->parseFiles($files));
            }
            else
            {
                $file = $this->input['file'];

                $this->params['body'] = $this->parseBody();

                // Now that we have added all POST params, we add the file itself
                // This contains the field name to be used for the file field
                $fileFieldName = $this->input['file_name'];

                $postFile = $this->getFileBodyFromFileInput($fileFieldName, $file);

                if (isset($postFile))
                {
                    $this->params['body'][$fileFieldName] = $postFile;
                }
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

    protected function parseFiles($files)
    {
        $fileBody = [];

        // we use array flatten to send multipart request through guzzle
        $flattenedFiles = $this->arrayFlatten($files);

        foreach ($flattenedFiles as $key => $val)
        {
            if ($val instanceof \SplFileInfo)
            {
                $fileBody[$key] = $this->getFileBodyFromFileInput($key, $val);
            }
        }

        return $fileBody;
    }

    protected function arrayFlatten(array &$messages, array $subnode = null, $path = null)
    {
        if (null === $subnode)
        {
            $subnode = &$messages;
        }

        foreach ($subnode as $key => $value)
        {
            if (is_array($value))
            {
                $nodePath = $path ? $path.'['.$key.']' : $key;

                $this->arrayFlatten($messages, $value, $nodePath);

                if (null === $path)
                {
                    unset($messages[$key]);
                }
            }
            elseif (null !== $path)
            {
                $messages[$path.'['.$key.']'] = $value;
            }
        }

        return $messages;
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

            $start_time = microtime(true);

            $spanOptions = (new ApiRequestSpan($this->client))::getRequestSpanOptions(ApiUrl::getApiBaseUrl().$this->path);

            $response = (new ApiRequestSpan($this->client))->wrapRequestInSpan(
                $method,
                $this->path,
                [
                    'options' => $this->params,
                    'headers' => $this->params['headers'] ?? [],
                ],
                $spanOptions
            )->json();

            $end_time = microtime(true);

            $time_taken = $end_time - $start_time;

            // log if response time is more then 180 seconds
            if ($time_taken > 180)
            {
                Trace::info(TraceCode::API_SLOW_RESPONSE_CALL, [
                    'api_response_time' => $time_taken,
                ]);
            }

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

    private function wrapRequestInSpan($methodName, $defaultSpanOptions = array())
    {
        $span = SpanTrace::startSpan($defaultSpanOptions);
        $scope = SpanTrace::withSpan($span);

        // inject spanContext into trace propagation headers
        $headers = [];
        if (empty($this->params['headers']) === false)
        {
            $headers = $this->params['headers'];
        }

        $path = $this->path;

        $arrHeaders = new ArrayHeaders($headers);

        SpanTrace::injectContext($arrHeaders);

        $headers = $arrHeaders->toArray();

        $methodArgs[1] = $headers;

        $methodTag = $methodName;

        $span->addAttribute('http.method', $methodTag);

        // handle actual request
        try {
            $client = $this->client
                ->$methodName($path, $this->params);
        }
        catch (\Throwable $e)
        {
            Trace::info(TraceCode::JAEGER_INFO, [
                'message'   => $e->getMessage(),
                'code'      => $e->getCode(),
                'stack'     => $e->getTraceAsString(),
            ]);

            $span->addAttribute('error', 'true');

            throw $e;
        }
        finally {
            $scope->close();
        }

        if (!is_null($client->json()))
        {
            // add response status as a span tags
            $httpCode = $client->getStatusCode();

            $span->addAttribute('http.status_code', $httpCode);

            if ($httpCode >= 400)
            {
                $span->addAttribute('error', 'true');
            }
        }

        return $client->json;
    }
}
