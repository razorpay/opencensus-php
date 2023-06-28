<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Address\Entity as AddressEntity;

class Address
{

    protected MerchantV1\Address $proto;

    function __construct(MerchantV1\Address $proto)
    {
        $this->proto = $proto;
    }

    public function ToEntity(): AddressEntity
    {
        $addressRawAttributes = [
            AddressEntity::ID => $this->proto->getId(),
            AddressEntity::CREATED_AT => $this->proto->getCreatedAt(),
            AddressEntity::UPDATED_AT => $this->proto->getUpdatedAt(),
            AddressEntity::ENTITY_ID => $this->proto->getEntityIdUnwrapped(),
            AddressEntity::ENTITY_TYPE => $this->proto->getEntityTypeUnwrapped(),
            AddressEntity::TYPE => $this->proto->getType(),
            AddressEntity::PRIMARY => $this->proto->getPrimary(),
            AddressEntity::LINE1 => $this->proto->getLine1(),
            AddressEntity::LINE2 => $this->proto->getLine2Unwrapped(),
            AddressEntity::ZIPCODE => $this->proto->getZipcodeUnwrapped(),
            AddressEntity::CITY => $this->proto->getCityUnwrapped(),
            AddressEntity::STATE => $this->proto->getStateUnwrapped(),
            AddressEntity::COUNTRY => $this->proto->getCountry(),
            AddressEntity::CONTACT => $this->proto->getContactUnwrapped(),
            AddressEntity::NAME => $this->proto->getNameUnwrapped(),
            AddressEntity::TAG => $this->proto->getTagUnwrapped(),
            AddressEntity::LANDMARK => $this->proto->getLandmarkUnwrapped(),
            AddressEntity::SOURCE_ID => $this->proto->getSourceIdUnwrapped(),
            AddressEntity::SOURCE_TYPE => $this->proto->getSourceTypeUnwrapped(),
            AddressEntity::DELETED_AT => $this->proto->getDeletedAtUnwrapped(),
        ];

        $addressEntity = new AddressEntity();
        $addressEntity->setRawAttributes($addressRawAttributes);
        $addressEntity->exists = true;
        return $addressEntity;
    }
}
