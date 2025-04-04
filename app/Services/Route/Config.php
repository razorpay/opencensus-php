<?php

namespace RZP\Services\Route;

use RZP\Models\Admin;

const DEFAULT_REQUEST_TIMEOUT = 60;
const DEFAULT_LOG_REQUEST = true;
const DEFAULT_LOG_RESPONSE = true;

const DEFAULT_TOKEN = false;

const REQUEST_TIMEOUT_KEY = 'request_timeout';

const LOG_REQUEST_KEY = 'log_request';
const LOG_RESPONSE_KEY = 'log_response';
const NEW_PASSPORT_TOKEN = 'new_passport_token';
const EXTERNAL_QUERY = 'external_query';


class Config
{
    protected $config;

    public function __construct()
    {
        $this->config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::ROUTE_SERVICE_CONFIG]) ?? [];
    }

    public function getRequestTimeout()
    {
        return $this->config[REQUEST_TIMEOUT_KEY] ?? DEFAULT_REQUEST_TIMEOUT;
    }

    public function shouldLogRequest()
    {
        return $this->config[LOG_REQUEST_KEY] ?? DEFAULT_LOG_REQUEST;
    }

    public function shouldLogResponse()
    {
        return $this->config[LOG_RESPONSE_KEY] ?? DEFAULT_LOG_RESPONSE;
    }

    public function shouldCreateNewPassportToken()
    {
        return $this->config[NEW_PASSPORT_TOKEN] ?? DEFAULT_TOKEN;
    }

    public function isExternalQueryEnabled($query)
    {
        return $this->config[EXTERNAL_QUERY][$query] ?? false;
    }
}
