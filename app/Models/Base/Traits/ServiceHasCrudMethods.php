<?php

namespace RZP\Models\Base\Traits;

/**
 * This trait if used in a Service supports CRUD methods.
 *
 * Expects:
 * - $this->core
 * - $this->entityRepo
 * - $this->merchant
 */
trait ServiceHasCrudMethods
{
    public function fetch(string $id): array
    {
        $entity = $this->entityRepo
                       ->findByPublicIdAndMerchant($id, $this->merchant);

        return $entity->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $entities = $this->entityRepo
                         ->fetch($input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    public function create(array $input): array
    {
        $entity = $this->core->create($input, $this->merchant);

        return $entity->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $entity = $this->entityRepo
                       ->findByPublicIdAndMerchant($id, $this->merchant);

        $entity = $this->core->update($entity, $input);

        return $entity->toArrayPublic();
    }

    public function delete(string $id)
    {
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $this->core->delete($entity);

        return $entity->toArrayDeleted();
    }
}
