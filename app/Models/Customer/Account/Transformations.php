<?php

namespace RZP\Models\Customer\Account;

use RZP\Models\Base\PublicCollection;
use RZP\Models\Customer\Entity;

class Transformations
{
    public function fillV2CustomerInfoInCustomerEntity($customer, $v2Data)
    {
        $entityData = [
            Entity::ID             => $v2Data[Entity::ID] ?? null,
            Entity::ENTITY         => $v2Data[Entity::ENTITY] ?? null,
            Entity::NAME           => $v2Data['first_name'] ?? null,
            Entity::EMAIL          => $v2Data[Entity::EMAIL] ?? null,
            Entity::CONTACT        => $v2Data[Entity::CONTACT] ?? null,
            Entity::GSTIN          => $v2Data['tax_details'][0]['value'] ?? null,
            Entity::NOTES          => $v2Data[Entity::NOTES] ?? [],
        ];
        $customer->fill($entityData);
        $customer->setAttribute(Entity::CREATED_AT, $v2Data[Entity::CREATED_AT]);

        if (array_key_exists('global_customer_id', $v2Data['custom_data']))
        {
            $customer->setAttribute(Entity::GLOBAL_CUSTOMER_ID, $v2Data['custom_data']['global_customer_id']);
        }
    }

    public function transformV1CreateOptionsToV2CreateOptions($opt, $merchantId)
    {
        return [
            'salutation'          =>        null,
            'first_name'          =>        $opt['name'] ?? null,
            'middle_name'         =>        null,
            'last_name'           =>        null,
            'email'               =>        $opt['email'] ?? null,
            'contact'             =>        $opt['contact'] ? (string)$opt['contact'] : null,
            'fail_existing'       =>        false,
            'notes'               =>        (object)$opt["notes"] ?? [],
            'gender'              =>        null,
            'dob'                 =>        null,
            'custom_data'         =>        (object)([]),
            'tax_details'         =>        $this->convertGstinToTaxDetails($opt['gstin'] ?? null),
            'merchant_id'         =>        $merchantId,
        ];
    }

    // Helper function to convert Gstin to TaxDetails
    public function convertGstinToTaxDetails($gstin)
    {
        if (empty($gstin)) {
            return null;
        }

        return [
            [
                'type' => 'IN_GST',
                'value' => $gstin
            ]
        ];
    }

    public function convertListResponseToPublicCollection($response): PublicCollection
    {
        $collection = new PublicCollection();
        foreach ($response['items'] as $item)
        {
            $custEntity = new Entity();
            $this->fillV2CustomerInfoInCustomerEntity($custEntity, $item);
            $collection->push($custEntity);
        }

        return $collection;
    }


}
