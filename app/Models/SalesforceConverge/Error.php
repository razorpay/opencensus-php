<?php
namespace RZP\Models\SalesforceConverge;

class Error
{
    protected $error;

    protected $message;

    protected $code;

    protected $reference;

    public function __construct(array $response)
    {
        $this->error     = $response[1];
        $this->message   = $response[3];
        $this->code      = null;//$response[Constants::CODE];
        $this->reference = null;//$response[Constants::REFERENCE];
    }
}
