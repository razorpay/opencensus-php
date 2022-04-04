<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;



use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\M2MReferral\Entity as M2MEntity;
use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;

class ReferralLinkRequest  extends FriendBuyRequest
{
    public $email;

    public $campaignId;

    public $customerId;


    public function __construct(Entity $entity)
    {
        $this->campaignId = env(Constants::M2M_FRIEND_BUY_CAMPAIGN_ID);

        $this->email = $entity->getContactEmail();

        $this->customerId = $entity->getMerchantId();
    }

    public function toArray(): array
    {
        $arrayRequest = [];

        if (empty($this->campaignId) === false)
        {
            $arrayRequest[Constants::CAMPAIGN_ID] = $this->campaignId;
        }
        if (empty($this->email) === false)
        {
            $arrayRequest[Constants::EMAIL] = $this->email;
        }
        else
        {
            $arrayRequest[Constants::EMAIL] = $this->customerId . "@email.com";
        }
        if (empty($this->customerId) === false)
        {
            $arrayRequest[Constants::CUSTOMER_ID] = $this->customerId;
        }

        return $arrayRequest;
    }

    public function getPath()
    {
        return "/v1/personal-referral-link";
    }
}
