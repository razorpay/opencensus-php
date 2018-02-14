<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Trace\TraceCode;
use Requests;

class ShieldClient
{
    const RULES = '/rules/';

    const EVALUATE = "/rules/evaluate";

    const X_RULESET = 'x-ruleset';

    const CONTENT_TYPE = 'content-type';

    protected $config;

    protected $baseUrl;

    protected $trace;

    protected $ruleset;


    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.shield');

        $this->trace = $app['trace'];

        $this->ruleset = $this->config['ruleset'];

        $this->baseUrl = $this->config['url'];
    }

    public function createRule(array $input)
    {
        return $this->sendRequest(self::RULES, Requests::POST, $input);
    }

    public function getRules()
    {
        return $this->sendRequest(self::RULES, Requests::GET);
    }

    public function getRuleById(string $id)
    {
        return $this->sendRequest(self::RULES . $id, Requests::GET);
    }

    public function deleteRuleById(string $id)
    {
        return $this->sendRequest(self::RULES . $id, Requests::DELETE);
    }

    public function updateRuleById(string $id, array $input)
    {
        return $this->sendRequest(self::RULES . $id, Requests::PUT, $input);
    }

    public function evaluateRules($input)
    {
        return $this->sendRequest(self::EVALUATE, Requests::POST, $input);
    }

    private function sendRequest(string $path, string $method, array $data = [])
    {
        $headers = $this->getShieldHeaders();
        $options = [
            "auth" => $this->getAuthHeaders()
        ];

        $content = '';
        if (empty($data) === false)
        {
             $content = json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        $url = $this->baseUrl . $path;

        try
        {
            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );
        }
        catch(\Requests_Exception $e)
        {
            $data = [
                "exception"     => $e->getMessage(),
                "url"           => $url,
                "input"         => $data,
                "response_code" => $response->status_code,
                "response_body" => $response->body
            ];

            $this->trace->error(TraceCode::SHIELD_INTEGRATION_ERROR, $data);
        }

        return json_decode($response->body, true);
    }

    private function getAuthHeaders() : array
    {
        return [
            $this->config['auth']['username'],
            $this->config['auth']['password'],
        ];
    }

    private function getShieldHeaders() : array
    {
        return [
            self::X_RULESET    => $this->ruleset,
            self::CONTENT_TYPE => "application/json",
        ];
    }

}
