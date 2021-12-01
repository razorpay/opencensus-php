<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;

class Error
{
    protected $error;

    protected $message;

    protected $code;

    protected $reference;

    public function __construct(array $response)
    {
        $this->error     = $response[Constants::ERROR];
        $this->message   = $response[Constants::MESSAGE];
        $this->code      = $response[Constants::CODE];
        $this->reference = $response[Constants::REFERENCE];
    }
}
