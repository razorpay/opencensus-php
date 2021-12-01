<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;



class AuthorizationRequest
{
    public $key;

    public $secret;

    public $path;

    public function __construct($key, $secret)
    {
        $this->key = $key;

        $this->secret = $secret;
    }

    public function getPath()
    {
        return "/v1/authorization";
    }
}
