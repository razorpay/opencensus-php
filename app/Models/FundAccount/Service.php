<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Customer;
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

        (new Validator)->setStrictFalse()->validateInput(Validator::BEFORE_CREATE, $input);

        $source = null;

        if (isset($input[Entity::CONTACT_ID]) === true)
        {
            /** @var Contact\Entity $source */
            $source = $this->repo->contact->findByPublicIdAndMerchant($input[Entity::CONTACT_ID], $this->merchant);
        }
        else if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            /** @var Customer\Entity $source */
            $source = $this->repo->customer->findByPublicIdAndMerchant($input[Entity::CUSTOMER_ID], $this->merchant);
        }

        $entity = $this->core->create($input, $this->merchant, $source);

        return $entity->toArrayPublic();
    }
}
