<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;

use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;
use RZP\Models\Merchant\M2MReferral\Entity as M2MEntity;
class MtuEventRequest extends FriendBuyRequest
{
    public    $orderId;

    public    $customerId;

    public    $amount;

    public    $currency;

    public    $email;

    public    $isNewCustomer;

    public    $referralCode;

    public    $authToken;

    protected $name;

    public function __construct(M2MEntity $m2mEntity)
    {
        parent::__construct();

        $this->isNewCustomer = true;

        $this->email = $m2mEntity->getValueFromMetaData(Constants::EMAIL);

        $this->customerId = $m2mEntity->getRefereeId();

        $this->referralCode = $m2mEntity->getValueFromMetaData(M2MConstants::REFERRAL_CODE);

        $this->amount = $m2mEntity->getValueFromMetaData(Constants::AMOUNT);

        $this->currency = $m2mEntity->getValueFromMetaData(Constants::CURRENCY);

        $this->orderId = $m2mEntity->getId();

        $this->name = substr($m2mEntity->getValueFromMetaData(M2MConstants::FIRST_NAME), 0, 32);

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
        if (empty($this->isNewCustomer) === false)
        {
            $arrayRequest[Constants::IS_NEW_CUSTOMER] = $this->isNewCustomer;
        }
        if (empty($this->eventType) === false)
        {
            $arrayRequest[Constants::EVENT_TYPE] = $this->eventType;
        }
        if (empty($this->amount) === false)
        {
            $arrayRequest[Constants::AMOUNT] = ($this->amount) / 100;
        }
        if (empty($this->currency) === false)
        {
            $arrayRequest[Constants::CURRENCY] = $this->currency;
        }
        if (empty($this->orderId) === false)
        {
            $arrayRequest[Constants::ORDER_ID] = $this->orderId;
        }
        if (empty($this->name) === false)
        {
            $arrayRequest[M2MConstants::FIRST_NAME] = $this->name;
        }

        return $arrayRequest;
    }

    public function getPath()
    {
        return "/v1/event/purchase";
    }
}
