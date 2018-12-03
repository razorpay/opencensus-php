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
    use Base\Traits\ServiceHasCrudMethods;

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

        $this->entityRepo = $this->repo->fund_account;
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
        /** @var Entity $fundAccount */
        $fundAccount = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $this->core->delete($fundAccount);

        return $fundAccount->toArrayDeleted();
    }

    public function fetchContact(string $contactId): Contact\Entity
    {
        /** @var Contact\Entity $contact */
        $contact = $this->repo->contact->findByPublicIdAndMerchant($contactId, $this->merchant);

        return $contact;
    }
}
