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

    public function create(array $input): array
    {
        $contactId = array_pull($input, Entity::CONTACT_ID);

        /** @var Contact\Entity $contact */
        $contact = $this->repo->contact->findByPublicIdAndMerchant($contactId, $this->merchant);

        $entity = $this->core->create($input, $this->merchant, $contact);

        return $entity->toArrayPublic();
    }
}
