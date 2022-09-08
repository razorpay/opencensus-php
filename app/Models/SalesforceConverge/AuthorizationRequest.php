<?php
namespace RZP\Models\SalesforceConverge;

class AuthorizationRequest
{
    public $username;

    public $password;

    public $client_id;

    public $client_secret;

    public function __construct($config)
    {
        $this->username = $config[Constants::USERNAME];
        $this->password = $config[Constants::PASSWORD];
        $this->client_id = $config[Constants::CLIENT_ID];
        $this->client_secret = $config[Constants::CLIENT_SECRET];
    }

    public function getPath()
    {
        return '/services/oauth2/token';
    }
}
