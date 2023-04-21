<?php

namespace RZP\Services;

use App;
use Config;
use Request;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Media
{
    const UPLOAD_PATH            = "/upload/file";
    const GET_BUCKETS_PATH       = '/buckets';
    const UPLOAD_PROCESS_PATH    = '/upload/process';
    const POST                   = "POST";
    const GET                    = "GET";
    const DELETE                 = "DELETE";
    const FILE_KEY               = "file";
    const CONTENT_TYPE_MULTIPART = "multipart/form-data";
    const CONTENT_TYPE_JSON      = "application/json";
    const REQUEST_REDACT_FIELDS = [
        'content',
        'headers',
    ];

    protected $app;

    protected $trace;

    protected $config;

    protected $ba;

    protected $requestHeader;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->config = $app['config']->get('applications.media_service');

        $this->trace = $app['trace'];
        $this->ba = $app['basicauth'];

        $this->requestHeader = [];

    }

    public function postUploadFile($input)
    {
        $response = $this->makeRequest(SELF::POST, SELF::UPLOAD_PATH, $input);
        return $response;
    }

    public function getBuckets()
    {
        $response = $this->makeRequest(SELF::GET, SELF::GET_BUCKETS_PATH, []);
        return $response;
    }

    public function postUploadProcess($input)
    {
        $response = $this->makeRequest(SELF::POST, SELF::UPLOAD_PROCESS_PATH, $input);
        return $response;
    }

    protected function makeRequest(string $method, string $path, array $content)
    {
        $contentType = SELF::CONTENT_TYPE_JSON;

        if ((isset($content[self::FILE_KEY]) === true) and (is_null($content[self::FILE_KEY]) === false))
        {
            $contentType = SELF::CONTENT_TYPE_MULTIPART;
        }
        else
        {
            unset($content[self::FILE_KEY]);

            if (empty($content) === false)
            {
                $content = json_encode($content);
            }
        }

        $auth = "Basic " . base64_encode($this->config['user'] . ":" . $this->config['password']);

        $request = [
            'content'            => $content,
            'method'             => $method,
            'headers'            => [
                'Content-Type'   => $contentType,
                'Authorization'  => $auth
            ],
            'url'                => $this->config['url'] . $path,
        ];

        $trace_request = $this->getRedactedRequest($request);

        $this->trace->info(TraceCode::MEDIA_SERVICE_REQUEST,
            $trace_request
        );

        if ($contentType === self::CONTENT_TYPE_MULTIPART)
        {
            list($responseBody, $statusCode) = $this->makeCurlRequest($request);
        }
        else
        {
            list($responseBody, $statusCode) = $this->sendRequest($request);
        }
        $responseBody = json_decode($responseBody, true);

        $this->trace->info(TraceCode::MEDIA_SERVICE_RESPONSE,
            [
                'response'       => $responseBody,
                'response_code'  => $statusCode
            ]
        );

        if ($statusCode != 200)
        {
            $this->trace->info(TraceCode::MEDIA_SERVICE_ERROR,
                [
                    'response' => $responseBody,
                    'status' => $statusCode
                ]
            );
        }
        return ['body' => $responseBody, 'status' => $statusCode];
    }

    private function getRedactedRequest(array $request) : array
    {
        foreach (self::REQUEST_REDACT_FIELDS as $field)
        {
            unset($request[$field]);
        }

        return $request;
    }

    protected function makeCurlRequest(array &$request)
    {
        $mime_boundary = md5(time()); // nosemgrep : php.lang.security.weak-crypto.weak-crypto

        $curl = curl_init();

        $headers = array (
            "authorization: " . $request['headers']['Authorization'],
            "content-type: " . 'multipart/form-data; boundary=' . $mime_boundary
        );

        $content = $this->getCurlData($request['content'], $mime_boundary,$request['content'][self::FILE_KEY]);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $request['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => SELF::POST,
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);

        $curlInfo = curl_getinfo($curl);

        curl_close($curl);

        return [$response, $curlInfo['http_code']];
    }

    protected function getCurlData($content, $mime_boundary, UploadedFile $file)
    {
        $eol = "\r\n";

        $data = '';

        if (isset($content[self::FILE_KEY]) === true)
        {
            $data .= "--" . $mime_boundary . $eol;
            $data .= 'Content-Disposition: form-data; name="file"; filename="' . $file->getClientOriginalName() . '"' . $eol;
            $data .= 'Content-Transfer-Encoding: binary'.$eol.$eol;
            $data .= file_get_contents($file) . $eol;
        }

        foreach ($content as $key => $value)
        {
            $data .= '--' . $mime_boundary . $eol;
            $data .= 'Content-Disposition: form-data; name="' . $key . '"' . $eol . $eol;
            $data .= $value . $eol;
        }

        $data .= '--' . $mime_boundary . '--';

        return $data;
    }

    protected function sendRequest($options)
    {
        $headers = $options['headers'];
        $auth = $this->getRequestAuth();
        $method = $options['method'];
        $url = $options['url'];

        $requestPayload = $this->getPayload(
            $options['content'] ?? [], $method);

        $requestOptions = [
            'auth'  => $auth,
        ];

        $response = Requests::request(
            $url,
            $headers,
            $requestPayload,
            $method,
            $requestOptions);

        return [$response->body, $response->status_code];
    }

    protected function getPayload($data, $method)
    {
        return $data;
    }

    protected function getRequestAuth()
    {
        return [$this->config['user'], $this->config['password']];
    }

}
