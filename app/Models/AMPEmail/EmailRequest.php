<?php
namespace RZP\Models\AMPEmail;


use RZP\Http\RequestHeader;

abstract class EmailRequest
{
    public $email;

    public $campaignId;

    public $token;

    public $config;


    public function __construct($campaignId, $email, $token)
    {
        $this->campaignId = $campaignId;

        $this->email = $email;

        $this->token = $token;

        $this->config = config(Constants::APPLICATIONS_MAILMODO);
    }

    public function getPath()
    {
        return $this->config[Constants::TRIGGER_EMAIL_ENDPOINT] . '/' . $this->campaignId;
    }

    public function getHeaders()
    {
        $headers = [
            Constants::CONTENT_TYPE => Constants::APPLICATION_JSON,
            Constants::ACCEPT       => Constants::APPLICATION_JSON,
        ];

        $headers[$this->config[Constants::AUTH][Constants::KEY]] = $this->config[Constants::AUTH][Constants::SECRET];

        return $headers;
    }

    public abstract function getPayload(): array;
}
