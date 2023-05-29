<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;

class MerchantEmail
{
    protected MerchantV1\Email $proto;

    function __construct(MerchantV1\Email $proto)
    {
        $this->proto = $proto;
    }

    public function ToEntity(): MerchantEmailEntity
    {
        $merchantEmailRawAttributes = [
            MerchantEmailEntity::ID => $this->proto->getId(),
            MerchantEmailEntity::TYPE => $this->proto->getType(),
            MerchantEmailEntity::MERCHANT_ID => $this->proto->getMerchantId(),
            MerchantEmailEntity::EMAIL => $this->proto->getEmailUnwrapped(),
            MerchantEmailEntity::VERIFIED => $this->proto->getVerified(),
            MerchantEmailEntity::PHONE => $this->proto->getPhoneUnwrapped(),
            MerchantEmailEntity::POLICY => $this->proto->getPolicyUnwrapped(),
            MerchantEmailEntity::URL => $this->proto->getUrlUnwrapped(),
            MerchantEmailEntity::CREATED_AT => $this->proto->getCreatedAt(),
            MerchantEmailEntity::UPDATED_AT => $this->proto->getUpdatedAt(),
        ];

        $merchantEmailEntity = new MerchantEmailEntity();
        $merchantEmailEntity->setRawAttributes($merchantEmailRawAttributes);
        $merchantEmailEntity->exists = true;
        return $merchantEmailEntity;
    }
}
