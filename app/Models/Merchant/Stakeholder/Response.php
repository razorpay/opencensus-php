<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Base;
use RZP\Models\Address;

class Response extends Base\Core
{
    public function createListResponse(Base\PublicCollection $stakeholders): array
    {
        $data = ['entity' => 'collection', 'items' => [], 'count' => $stakeholders->count()];

        foreach ($stakeholders as $stakeholder)
        {
            $data['items'][] = $this->createResponse($stakeholder);
        }

        return $data;
    }

    public function createResponse(Entity $stakeholder): array
    {
        $response = [
            Entity::ID              => $stakeholder->getPublicId(),
            'entity'                => $stakeholder->getEntity(),
            Constants::RELATIONSHIP => [],
            Constants::PHONE        => [],
            Entity::NOTES           => $stakeholder->getNotes(),
            Constants::KYC          => [],
        ];

        $attributes = [
            Entity::NAME                 => Entity::NAME,
            Entity::EMAIL                => Entity::EMAIL,
            Entity::PERCENTAGE_OWNERSHIP => Entity::PERCENTAGE_OWNERSHIP,
        ];

        $this->addAttributesToResponse($stakeholder, $attributes, $response);

        $attributes = [
            Entity::DIRECTOR  => Entity::DIRECTOR,
            Entity::EXECUTIVE => Entity::EXECUTIVE,
        ];

        $this->addAttributesToResponse($stakeholder, $attributes, $response[Constants::RELATIONSHIP]);

        $attributes = [
            Constants::PRIMARY   => Entity::PHONE_PRIMARY,
            Constants::SECONDARY => Entity::PHONE_SECONDARY,
        ];
        $this->addAttributesToResponse($stakeholder, $attributes, $response[Constants::PHONE]);

        $address = $this->repo->address->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, Constants::RESIDENTIAL);

        if (empty($address) === false)
        {
            $response[Constants::ADDRESSES][Constants::RESIDENTIAL] = [];

            $attributes = [
                Constants::STREET      => Address\Entity::LINE1,
                Constants::CITY        => Address\Entity::CITY,
                Constants::STATE       => Address\Entity::STATE,
                Constants::POSTAL_CODE => Address\Entity::ZIPCODE,
                Constants::COUNTRY     => Address\Entity::COUNTRY,
            ];
            $this->addAttributesToResponse($address, $attributes, $response[Constants::ADDRESSES][Constants::RESIDENTIAL]);
        }

        $attributes = [
            Constants::PAN => Entity::POI_IDENTIFICATION_NUMBER,
        ];
        $this->addAttributesToResponse($stakeholder, $attributes, $response[Constants::KYC]);

        return $response;
    }

    protected function addAttributesToResponse($entity, $attributes, & $response)
    {
        foreach ($attributes as $key => $val)
        {
            $value = $entity->getAttribute($val);

            if (empty($value) === false)
            {
                $response[$key] = $value;
            }
        }
    }
}
