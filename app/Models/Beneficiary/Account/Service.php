<?php

namespace RZP\Models\Beneficiary\Account;

use RZP\Models\Base;
use RZP\Models\Beneficiary;

/**
 * Class Service
 *
 * @package RZP\Models\Beneficiary\Account
 */
class Service extends Base\Service
{
    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->beneficiary_account;
    }

    public function fetch(string $beneId, string $id, array $input): array
    {
        $bene = $this->fetchBeneficiary($beneId);

        $entity = $this->entityRepo
                       ->findByPublicIdAndBeneficiaryAndMerchant($id, $bene, $this->merchant, $input);

        return $entity->toArrayPublic();
    }

    public function fetchMultiple(string $beneId, array $input): array
    {
        $input[Entity::BENEFICIARY_ID] = Beneficiary\Entity::verifyIdAndStripSign($beneId);

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

    public function fetchBeneficiary(string $beneId): Beneficiary\Entity
    {
        /** @var Beneficiary\Entity $beneficiary */
        $beneficiary = $this->repo->beneficiary->findByPublicIdAndMerchant($beneId, $this->merchant);

        return $beneficiary;
    }
}
