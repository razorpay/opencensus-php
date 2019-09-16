<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Contact;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;

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

    public function create(array $input, string $batchId = null): array
    {
        $this->traceFundAccountNewRequest($input);

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

        if (optional($source)->isActive() === false)
        {
            throw new BadRequestValidationFailureException(
                'Fund accounts cannot be created on an inactive ' . $source->getEntity());
        }

        if ($batchId !== null)
        {
            $entity = $this->core->create($input, $this->merchant, $source, null, $batchId);
        }
        else
        {
            $entity = $this->core->create($input, $this->merchant, $source);
        }

        return $entity->toArrayPublic();
    }

    public function fetch(string $id, array $input): array
    {
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return $entity->toArrayPublic();
    }

    protected function traceFundAccountNewRequest(array $input)
    {
        $this->unsetSensitiveCardDetails($input);

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST, $input);
    }

    protected function unsetSensitiveCardDetails(array & $input)
    {
        if ((isset($input[Entity::CARD]) === true) and
            (is_array($input[Entity::CARD]) === true))
        {
            if (empty($input[Entity::CARD][Card\Entity::NUMBER]) === false)
            {
                $input[Entity::CARD][Card\Entity::IIN] = substr($input[Entity::CARD][Card\Entity::NUMBER], 0, 6);
            }

            unset($input[Entity::CARD][Card\Entity::CVV]);
            unset($input[Entity::CARD][Card\Entity::NUMBER]);
        }
    }
}
