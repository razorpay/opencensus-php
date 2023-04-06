<?php

namespace RZP\Models\Merchant\InternationalEnablement\Detail;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function getLatest(): ?Entity
    {
        $merchantId = $this->merchant->getId();

        return $this->repo->international_enablement_detail->getLatest($merchantId);
    }

    private function create(array $input, string $action): Entity
    {
        if (array_key_exists(Entity::PRODUCTS, $input) === true)
        {
            if ($input[Entity::PRODUCTS] === null)
            {
                $input[Entity::PRODUCTS] = [];
            }
        }

        $entity = new Entity;

        $createOperation = sprintf(Constants::VALIDATOR_CREATE_ACTION_KEY, $action);

        $entity->getValidator()->validateInput($createOperation, $input);

        $entity->getValidator()->setStrictFalse();

        $entity->build($input);

        if ($action === Constants::ACTION_SUBMIT)
        {
            $entity->markSubmitted();
        }

        $merchantId = $this->merchant->getId();

        $entity->setMerchantId($merchantId);

        return $entity;
    }

    public function upsert(array $input, string $action): array
    {
        $entity = $this->getLatest();

        $existingAttributesArray = [];

        $canPatchEntity = (is_null($entity) === false) && ($entity->isSubmitted() === false);

        if ($canPatchEntity === true)
        {
            $existingAttributesArray = $entity->toArrayPublic();

            unset($existingAttributesArray[Entity::CREATED_AT]);

            unset($existingAttributesArray[Entity::UPDATED_AT]);

            unset($existingAttributesArray[Entity::SUBMITTED_AT]);
        }
        else
        {
            // non-patchable entity, hence setting it back to null
            $entity = null;
        }

        $input = array_replace($existingAttributesArray, $input);

        $newEntity = $this->create($input, $action);

        if ($canPatchEntity === true)
        {
            $newEntity->setRevisionId($entity->getRevisionId());

            $this->repo->deleteOrFail($entity);
        }

        $this->repo->saveOrFail($newEntity);

        return [$entity, $newEntity];
    }
}
