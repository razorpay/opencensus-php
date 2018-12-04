<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Trace\TraceCode;

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
        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('create_fund_account', $input);

        // Contact ID is fetched and passed along as an Entity. Not required for create input
        $contactId = array_pull($input, Entity::CONTACT_ID);

        /** @var Contact\Entity $contact */
        $contact = $this->repo->contact->findByPublicIdAndMerchant($contactId, $this->merchant);

        $entity = $this->core->create($input, $this->merchant, $contact);

        return $entity->toArrayPublic();
    }
}
