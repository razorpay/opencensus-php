<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;

use RZP\Models\Merchant\M2MReferral\Entity as M2MEntity;
use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;
class SignUpEventRequest extends FriendBuyRequest
{
    protected $customerId;

    protected $email;

    protected $referralCode;

    protected $name;

    public    $authToken;

    public function __construct(M2MEntity $m2mEntity)
    {
        parent::__construct();

        $this->email = $m2mEntity->getValueFromMetaData(Constants::EMAIL);

        $this->customerId = $m2mEntity->getRefereeId();

        $this->referralCode = $m2mEntity->getValueFromMetaData(M2MConstants::REFERRAL_CODE);

        $this->name = substr($m2mEntity->getValueFromMetaData(M2MConstants::FIRST_NAME), 0, 32);

        return $this;
    }

    public function toArray(): array
    {
        $arrayRequest = [];

        if (empty($this->referralCode) === false)
        {
            $arrayRequest[M2MConstants::REFERRAL_CODE] = $this->referralCode;
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
        if (empty($this->name) === false)
        {
            $arrayRequest[M2MConstants::FIRST_NAME] = $this->name;
        }

        return $arrayRequest;
    }

    public function getPath()
    {
        return "/v1/event/account-sign-up";
    }
}
