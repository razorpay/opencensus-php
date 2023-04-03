<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;

class Templating
{
    // Headers for templating-service
    const OWNER_ID_HEADER        = 'X-Owner-Id';
    const OWNER_TYPE_HEADER      = 'X-Owner-Type';

    // Standard URL Paths
    const NAMESPACES_PATH        = '/namespaces';
    const TEMPLATE_CONFIGS_PATH  = '/template_configs';
    const TEMPLATE_SEARCH_PATH   = '/templates/search';
    const TEMPLATE_VIEW_PATH     = '/template_configs/view';
    const ROLE_ASSIGN_PATH       = '/user_roles/assign';
    const ROLE_REVOKE_PATH       = '/user_roles/revoke';
    const PRE_PROCESSOR_PATH     = '/preprocessor';
    const PRE_PROCESSOR_VIEW_PATH = '/template_configs/view/preprocessor';


    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.templating');

        $this->trace = $app['trace'];

        $this->ba = $app['basicauth'];

        $this->requestHeader = [];

    }

    public function createNamespace($input = Array())
    {
        return $this->sendRequest([
            'path'      => self::NAMESPACES_PATH,
            'data'      => $input,
            'method'    => 'POST',
        ]);
    }

    public function listNamespace($input = Array())
    {
        return $this->sendRequest([
            'path'      => self::NAMESPACES_PATH,
            'data'      => $input,
            'method'    => 'GET',
        ]);
    }

    public function getTemplateConfig(string $id)
    {
        return $this->sendRequest(
        [
            'path'      => self::TEMPLATE_CONFIGS_PATH.'/'.$id,
            'method'    => 'GET',
        ]);
    }

    public function listTemplateConfig($params = Array())
    {
        return $this->sendRequest(
        [
            'path'      => self::TEMPLATE_CONFIGS_PATH,
            'data'      => $params,
            'method'    => 'GET',
        ]);
    }

    public function viewTemplateConfig(string $id)
    {
        return $this->sendRequest(
        [
            'path'      => self::TEMPLATE_VIEW_PATH.'/'.$id,
            'method'    => 'GET',
        ]);
    }

    public function deleteTemplateConfig(string $id)
    {
        return $this->sendRequest(
        [
            'path'      => self::TEMPLATE_CONFIGS_PATH.'/'.$id,
            'method'    => 'DELETE',
        ]);
    }

    public function testPreProcessor($input)
    {
        return $this->sendRequest(
            [
                'path'      => self::PRE_PROCESSOR_PATH.'/test',
                'data'      => $input,
                'method'    => 'POST',
            ]);
    }

    public function renderTemplate($input)
    {
        return $this->sendRequest(
            [
                'path'      => self::TEMPLATE_CONFIGS_PATH.'/render',
                'data'      => $input,
                'method'    => 'POST',
            ]);
    }

    public function createTemplateConfig($input)
    {
        return $this->sendRequest(
        [
            'path'      => self::TEMPLATE_CONFIGS_PATH,
            'data'      => $input,
            'method'    => 'POST',
        ]);
    }

    public function updateTemplateConfig($id, $input)
    {
        return $this->sendRequest(
        [
            'path'      => self::TEMPLATE_CONFIGS_PATH.'/'.$id,
            'data'      => $input,
            'method'    => 'PATCH',
        ]);
    }

    public function assignRole($input)
    {
        return $this->sendRequest(
        [
            'path'      => self::ROLE_ASSIGN_PATH,
            'data'      => $input,
            'method'    => 'POST',
        ]);
    }

    public function revokeRole($input)
    {
        return $this->sendRequest(
            [
                'path'      => self::ROLE_REVOKE_PATH,
                'data'      => $input,
                'method'    => 'POST',
            ]);
    }

    // @TODO: Split the sendRequest method into two
    // so that the funcitionality around forming the request params
    // can be tested
    protected function sendRequest($options)
    {
        $headers = $this->getHeaders();

        $auth = $this->getRequestAuth();

        $method = $options['method'];

        $requestPayload = $this->getPayload(
            $options['data'] ?? [],
            $method);

        $url = $this->config['url'].$options['path'];

        $requestOptions = [
            'auth'  => $auth,
        ];

        try {
            $response = Requests::request(
                $url,
                $headers,
                $requestPayload,
                $method,
                $requestOptions);

            return $this->handleResponse($response);
        }
        catch(\WpOrg\Requests\Exception $exception)
        {
            throw new ServerErrorException(
                'Unable to connect to templating service',
                ErrorCode::SERVER_ERROR_TEMPLATING_REQUEST_FAILURE,
                compact('headers', 'method', 'url'));
        }

    }

    protected function handleResponse($response)
    {
        $this->trace->info(
            TraceCode::TEMPLATING_RESPONSE,
            [
                'status_code'     => $response->status_code,
            ]);

        $responseBody = json_decode($response->body);

        if ($response->status_code >= 500)
        {
            throw new ServerErrorException(
                'Received Server Error in templating service response',
                ErrorCode::SERVER_ERROR_IN_TEMPLATING_RESPONSE,
                [ 'templating_error'    => $responseBody->error ]);
        }
        else if($response->status_code == 403)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_NAMESPACE_ACCESS_IN_TEMPLATING_RESPONSE,
                null,
                [
                    'templating_error'    => $responseBody->error,
                ]);
        }
        else if($response->status_code == 409)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ENTITY_ALREADY_EXISTS,
                null,
                [
                    'templating_error'    => $responseBody->error,
                ]);
        }
        else if($response->status_code >= 400)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_IN_TEMPLATING_RESPONSE,
                null,
                [
                    'templating_error'    => $responseBody->error,
                ]);
        }

        return $responseBody;
    }

    protected function getPayload($data, $method)
    {
        if ($method === 'GET'|| $method === 'DELETE') return $data;

        return json_encode($data, JSON_FORCE_OBJECT);
    }

    protected function getHeaders()
    {
        $requestHeaders = [];

        $adminId = $this->ba->getAdmin()->getId();

        $requestHeaders[self::OWNER_ID_HEADER] = $adminId;

        $requestHeaders['Content-Type'] = 'application/json';

        /**
         * If other than admin is ever able to make request
         * to templating_service
         * Then change the below header value appropiately
         */
        $requestHeaders[self::OWNER_TYPE_HEADER] = 'admin';

        return $requestHeaders;

    }


    protected function getRequestAuth()
    {
        return [$this->config['user'], $this->config['password']];
    }

}
