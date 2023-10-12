<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Address\Entity as AddressEntity;
use RZP\Models\Base\UniqueIdEntity;

class Address implements EntityToProtoConvertorInterface
{

    protected AddressEntity $entity;

    protected array $dirtyFieldKeys;

    function __construct(AddressEntity $entity, array $dirtyFieldKeys)
    {
        $this->entity = $entity;

        $this->dirtyFieldKeys = $dirtyFieldKeys;
    }

    /**
     * @throws \Exception
     */
    public function toSaveProtoRequest(): MerchantV1\SaveRequest
    {
        $saveRequest = new MerchantV1\SaveRequest();

        $addressSaveRequest = new MerchantV1\AddressSaveRequest();

        $address = new MerchantV1\Address();

        $rawAttributes = $this->entity->getAttributes();

        $address->setId(Helper::notNullCheck($rawAttributes, UniqueIdEntity::ID));
        $address->setEntityId(Helper::converToStringValue($rawAttributes, AddressEntity::ENTITY_ID));
        $address->setEntityType(Helper::converToStringValue($rawAttributes, AddressEntity::ENTITY_TYPE));
        $address->setLine1(Helper::notNullCheck($rawAttributes, AddressEntity::LINE1));
        $address->setLine2(Helper::converToStringValue($rawAttributes, AddressEntity::LINE2));
        $address->setCity(Helper::converToStringValue($rawAttributes, AddressEntity::CITY));
        $address->setZipcode(Helper::converToStringValue($rawAttributes, AddressEntity::ZIPCODE));
        $address->setState(Helper::converToStringValue($rawAttributes, AddressEntity::STATE));
        $address->setCountry(Helper::notNullCheck($rawAttributes, AddressEntity::COUNTRY));
        $address->setType(Helper::notNullCheck($rawAttributes, AddressEntity::TYPE));
        $address->setPrimary(Helper::convertBoolToNotNullableInt($rawAttributes, AddressEntity::PRIMARY, 1));
        $address->setContact(Helper::converToStringValue($rawAttributes, AddressEntity::CONTACT));
        $address->setTag(Helper::converToStringValue($rawAttributes, AddressEntity::TAG));
        $address->setLandmark(Helper::converToStringValue($rawAttributes, AddressEntity::LANDMARK));
        $address->setName(Helper::converToStringValue($rawAttributes, AddressEntity::NAME));
        $address->setSourceId(Helper::converToStringValue($rawAttributes, AddressEntity::SOURCE_ID));
        $address->setSourceType(Helper::converToStringValue($rawAttributes, AddressEntity::SOURCE_TYPE));

        $addressSaveRequest->setAddress($address);
        $addressSaveRequest->setFields($this->dirtyFieldKeys);

        $saveRequest->setAddressSaveRequests([$addressSaveRequest]);
        return $saveRequest;
    }
}
