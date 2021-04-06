<?php

namespace RZP\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;
use RZP\Gateway\P2p\Upi\Axis\BankAccountGateway;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function manageBulk(array $input): array
    {
        return $this->retrieveBanksSuccess([Entity::BANKS => $input]);
    }

    public function retrieveBanks(array $input): array
    {
        $this->initialize(Action::RETRIEVE_BANKS, $input, true);

        $this->context()->setHandleAndMode($input[Entity::HANDLE]);

        if(isset($input[Base\Libraries\Context::REQUEST_ID]) === true)
        {
            $this->context()->setOptions(new Base\Libraries\ArrayBag($input));
        }

        return $this->callGateway();
    }

    protected function retrieveBanksSuccess(array $input): array
    {
        $this->initialize(Action::RETRIEVE_BANKS_SUCCESS, $input, true);

        $banks = $this->core->createOrUpdateMany($this->input->get(Entity::BANKS));

        if($this->app['basicauth']->isCron() === true)
        {
            $banksThreshold = config('gateway.'. $this->getGateway() .'.bank_count_threshold');

            if($banks->count() >= $banksThreshold)
            {
                $this->core
                    ->disableBanksNotInListWithHandle($banks->getIds(), $this->context()->handleCode());
            }
        }

        return $banks->toArrayPublic();
    }

    protected function getEntity()
    {
        return camel_case(BankAccount\Entity::BANK_ACCOUNT);
    }
}
