<?php
namespace RZP\Models\SalesforceConverge;

class AuthToken
{
    protected $tokenType;

    protected $instanceUrl;

    protected $token;

    protected $error;

    public function __construct(array $response)
    {
        if (empty($response[Constants::ERROR]) === false)
        {
            $this->error = new Error($response);
        }

        $this->tokenType        = $response[Constants::TOKEN_TYPE] ?? '';
        $this->token            = $response[Constants::ACCESS_TOKEN] ?? '';
        $this->instanceUrl      = $response[Constants::INSTANCE_URL] ?? '';
    }

    public function getAuthorizationHeader()
    {
        return "Bearer ".$this->token;
    }

}
