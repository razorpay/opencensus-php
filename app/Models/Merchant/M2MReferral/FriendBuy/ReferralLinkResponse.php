<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;

use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;

class ReferralLinkResponse
{

    protected $referralCode;

    protected  $link;

    protected $error;

    public function __construct(array $response)
    {
        if (empty($response[Constants::ERROR]) === false)
        {
            $this->error = new Error($response);
        }

        $this->link   = $response[Constants::LINK] ?? '';

        $this->referralCode = $response[M2MConstants::REFERRAL_CODE] ?? '';
    }

    public function isSuccess()
    {
        return empty($this->error);
    }

    public function getReferralCode()
    {
        return $this->referralCode;
    }
    public function getReferralLink()
    {
        return $this->link;
    }
}
