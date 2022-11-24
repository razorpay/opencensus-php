<?php

namespace RZP\Services;

use Request;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Exception;
use Symfony\Component\HttpFoundation\Response;

class ReconService
{
    protected $baseUrl;

    const REQUEST_TIMEOUT = 60;

    const MERCHANT_ID = 'merchant_id';

    const WORKSPACE_ID = 'workspace_id';

    const FILE_TYPE_ID = 'file_type_id';

    const FILE = 'file';

    const NAME = 'name';

    const METHOD = 'method';

    const ART_UFH_FILE_TYPE = 'art_input';

    const ART_UFH_WORKFLOW_FILE_TYPE = 'art_workflow';

    const ADMIN_DASHBOARD_UPLOAD = 'admin_dashboard/upload';

    const ADMIN_DASHBOARD_WORKFLOW_UPLOAD = 'admin_dashboard/upload/workflow';

    const ART_UFH_BULK_RULE = 'art_bulk_rule';

    const ART_UFH_SAMPLE_FILE = 'art_sample_file';

    const BULK_RULE_URL = 'bulk_rule';

    const FILE_TYPE_URL = 'file_types';

    const SAMPLE_FILE_PARSER_URL = 'sample_file_parser';

    const POST = 'POST';

    const GET = 'GET';

    const PATCH = 'PATCH';

    const AUTH_TYPE = 'auth_type';

    const API = 'api';

    const NULL = 'null';

    const MATCHER = 'matcher';


    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.recon');

        $this->trace = $app['trace'];

        $this->ba = $app['basicauth'];

        $this->requestHeader = [];

        $this->baseUrl = $this->config['url'];

        $this->key = $this->config['api_key'];

        $this->auth = $app['basicauth'];

        $this->secret = $this->config['api_secret'];

        $this->matcher_key = $this->config['matcher_key'];

        $this->matcher_secret = $this->config['matcher_secret'];

        $this->ufh = (new UfhService($app));

    }

    public function sendAnyRequest($url, $method, $input)
    {
        $this->trace->info(
            TraceCode::RECON_SEND_ANY_REQUEST_INPUT_DATA,
            [
                'url'       => $url,
                'method'    => $method,
                'input'     => $input,
            ]);
        if (array_key_exists("body", $input))
        {
            $data = json_decode($input['body']);
        }
        else if ($method == self::GET) {
            $data = $input;
            unset($data[self::FILE]);
            unset($data[self::METHOD]);
            unset($data[self::AUTH_TYPE]);
        }
        else
        {
            $data = [];
        }
        $allowed_methods = [self::POST, self::PATCH];

        if ($url == self::BULK_RULE_URL and in_array($method, $allowed_methods))
        {
            $data->rule_file_path = $this->uploadBulkRuleFile($input);
            unset($input[self::FILE]);
        }

        if (in_array($url, [self::FILE_TYPE_URL, self::SAMPLE_FILE_PARSER_URL]) and in_array($method, $allowed_methods))
        {
            if(array_key_exists(self::FILE, $input) and !(is_null($input[self::FILE]) or  $input[self::FILE] == self::NULL))
            {
                $data->sample_file_path = $this->uploadSampleFile($input);
                unset($input[self::FILE]);
            }
        }

        $this->trace->info(
            TraceCode::RECON_SEND_REQUEST_DATA,
            [
                'url'       => $url,
                'method'    => $method,
                'input'     => $input,
                'data'      => $data,
            ]);

        if (array_key_exists(self::AUTH_TYPE, $input))
        {
            $auth_type = $input[self::AUTH_TYPE];
            unset($input[self::AUTH_TYPE]);
            return $this->sendRequest($url, $method, $data, $auth_type);
        }

        return $this->sendRequest($url, $method, $data);
    }

    public function uploadFile($input)
    {
        $merchant_id = $input[self::MERCHANT_ID];

        $workspace_id = $input[self::WORKSPACE_ID];

        $file_type_id = $input[self::FILE_TYPE_ID];

        $file = $input[self::FILE];

        $fileName = $file->getClientOriginalName();

        $storageFileName = self::ADMIN_DASHBOARD_UPLOAD . '/' . $merchant_id . '/' . $workspace_id . '/' . $file_type_id . '/' . $fileName;

        $input['file_path'] = $this->uploadFileToUfh($file, $storageFileName, self::ART_UFH_FILE_TYPE);

        unset($input[self::FILE]);

        return $this->sendRequest('file', 'POST', $input);
    }

    public function workflowFileUpload($input)
    {
        $merchant_id = $input[self::MERCHANT_ID];

        $workspace_id = $input[self::WORKSPACE_ID];

        $file_type_id = $input[self::FILE_TYPE_ID];

        $file = $input[self::FILE];

        $fileName = $file->getClientOriginalName();

        $storageFileName = self::ADMIN_DASHBOARD_WORKFLOW_UPLOAD . '/' . $merchant_id . '/' . $workspace_id . '/' . $file_type_id . '/' . $fileName;

        $input['file_path'] = $this->uploadFileToUfh($file, $storageFileName, self::ART_UFH_WORKFLOW_FILE_TYPE);

        unset($input[self::FILE]);

        return $this->sendRequest('workflow_file', 'POST', $input);
    }

    protected function uploadBulkRuleFile($input)
    {
        if (array_key_exists("body", $input))
        {
            $data = json_decode($input['body'], true);
        }
        else
        {
            $data = [];
        }

        $date = date('Y-m-d');

        $merchant_id = $data[self::MERCHANT_ID];

        $workspace_id = $data[self::WORKSPACE_ID];

        $file = $input[self::FILE];

        $fileName = $file->getClientOriginalName();

        $storageFileName = self::ADMIN_DASHBOARD_UPLOAD . '/' . $merchant_id . '/' . $workspace_id . '/bulk_rule/' . $date . '/' . $fileName;

        return $this->uploadFileToUfh($file, $storageFileName, self::ART_UFH_BULK_RULE);
    }

    protected function uploadSampleFile($input)
    {
        if (array_key_exists("body", $input))
        {
            $data = json_decode($input['body'], true);
        }
        else
        {
            $data = [];
        }

        $date = date('Y-m-d');

        $merchant_id = $data[self::MERCHANT_ID];

        $workspace_id = $data[self::WORKSPACE_ID];

        $name = $data[self::NAME];

        $file = $input[self::FILE];

        $fileName = $file->getClientOriginalName();

        $storageFileName = self::ADMIN_DASHBOARD_UPLOAD . '/' . $merchant_id . '/' . $workspace_id . '/' . $date . '/' . $name .  '/sample/' . $fileName;

        return $this->uploadFileToUfh($file, $storageFileName, self::ART_UFH_SAMPLE_FILE);
    }

    protected function uploadFileToUfh($file, $storageFileName, $type)
    {
        $response = $this->ufh->uploadFileAndGetUrl($file, $storageFileName, $type, null);

        $this->trace->info(
            TraceCode::UFH_FILE_UPLOAD, $response
        );

        return $response['relative_location'];
    }

    protected function sendRequest($url, $method, $data = null, $auth_type=self::API)
    {
        $headers['Content-Type'] = 'application/json';

        $requestPayload = $this->getPayload($data ?? [], $method);

        $url = $this->baseUrl . $url;

        if ($auth_type == self::MATCHER) {
            $auth = [$this->matcher_key, $this->matcher_secret];
        }
        else {
            $auth = [$this->key, $this->secret];
        }

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $auth,
        );


        $response = Requests::request(
            $url,
            $headers,
            $requestPayload,
            $method,
            $options);

        $this->trace->info(
            TraceCode::RECON_SERVICE_REQUEST,
            [
                'url'     => $url,
                'body'    => $requestPayload,
                'method'  => $method,
            ]);

        return $this->handleResponse($response);

    }

    protected function handleResponse($response)
    {
        $this->trace->info(
            TraceCode::RECON_SERVICE_RESPONSE,
            [
                'status_code'     => $response->status_code,
            ]);

        $statusCode = $response->status_code;

        if (($statusCode >= Response::HTTP_OK) and ($statusCode < Response::HTTP_BAD_REQUEST))
        {
            return json_decode($response->body);
        }
        else {
            $responseBody = json_decode($response->body, true);
            $this->trace->info(
                TraceCode::RECON_ERROR_RESPONSE,
                [
                    'status_code' => $statusCode,
                    'response' => $response,
                ]);

            if (($statusCode >= Response::HTTP_BAD_REQUEST) and ($statusCode < Response::HTTP_INTERNAL_SERVER_ERROR))
            {
                if (array_key_exists("message", $responseBody)){
                    $message = json_encode($responseBody['message']);
                }
                else{
                    $message = json_encode($responseBody);
                }
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        'status_code' => $statusCode,
                        'response_body' => $responseBody,
                    ],
                    $message
                );

            }
            else
            {
                throw new Exception\RuntimeException(
                    'Unexpected response code received from Ledger service.',
                    [
                        'status_code' => $statusCode,
                        'response_body' => $responseBody,
                    ]);
            }
        }
    }

    protected function getPayload($data, $method)
    {
        if ($method === 'GET') return $data;
        if ($method === 'DELETE') return $data;

        return json_encode($data);
    }
}
