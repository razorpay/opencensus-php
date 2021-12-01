<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;


use Carbon\Carbon;
use  DateTime;
use Carbon\CarbonTimeZone;

class AuthToken
{
    protected $tokenType;

    protected $expires;

    protected $token;

    protected $error;

    public function __construct(array $response)
    {
        if (empty($response[Constants::ERROR]) === false)
        {
            $this->error = new Error($response);
        }

        $this->tokenType = $response[Constants::TOKEN_TYPE] ?? '';
        $this->token     = $response[Constants::TOKEN] ?? '';
        $this->expires   = $response[Constants::EXPIRES] ?? '';
    }

    public function getAuthorizationHeader()
    {
        return $this->tokenType . ' ' . $this->token;
    }

    public function isExpired()
    {
        return Carbon::parse($this->expires)->getTimestamp() < Carbon::now()->getTimestamp();
    }
}
