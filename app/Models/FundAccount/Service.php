<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Models\Contact;

/**
 * Class Service
 *
 * @package RZP\Models\FundAccount
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
        $bene = $this->fetchContact($beneId);

        $entity = $this->entityRepo
                       ->findByPublicIdAndBeneficiaryAndMerchant($id, $bene, $this->merchant, $input);

        return $entity->toArrayPublic();
    }

    public function fetchMultiple(string $beneId, array $input): array
    {
        $input[Entity::CONTACT_ID] = Contact\Entity::verifyIdAndStripSign($beneId);

        $entities = $this->entityRepo
                         ->fetch($input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    public function create(string $beneId, array $input): array
    {
        $beneficiary = $this->fetchContact($beneId, $this->merchant);

        $entity = $this->core->create($input, $beneficiary, $this->merchant);

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

    public function fetchContact(string $contactId): Contact\Entity
    {
        /** @var Contact\Entity $beneficiary */
        $beneficiary = $this->repo->beneficiary->findByPublicIdAndMerchant($contactId, $this->merchant);

        return $beneficiary;
    }
}
