<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function add(array $input): array
    {
        $this->initialize(Action::ADD, $input, true);

        if ($this->core->checkLocalAvailability($this->input->get(Entity::USERNAME)))
        {
            throw new \Exception('Change the exception and message');
        }

        $this->gatewayInput->put(Entity::USERNAME, $this->input->get(Entity::USERNAME));

        if ($this->input->has(Entity::BANK_ACCOUNT_ID))
        {
            $bankAccount = (new BankAccount\Core)->fetch($this->input->get(Entity::BANK_ACCOUNT_ID));

            $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);
        }

        return $this->callGateway();
    }

    public function addSuccess(array $input): array
    {
        $this->initialize(Action::ADD_SUCCESS, $input, true);

        $vpa = $this->core->create($this->input->get(Entity::VPA));

        $bankAccountId = array_get($this->input->get(Entity::BANK_ACCOUNT), Entity::ID);

        if (is_null($bankAccountId) === false)
        {
            $bankAccount = (new BankAccount\Core)->fetch($bankAccountId);

            $this->core->assignBankAccount($vpa, $bankAccount);
        }

        return $vpa->toArrayPublic();
    }

    public function assignBankAccount(array $input): array
    {
        $this->initialize(Action::ASSIGN_BANK_ACCOUNT, $input, true);

        // Since we have same handle in context, we can assure that both
        // the vpa and bank account belongs to same handle
        $vpa         = $this->core->fetch($this->input->get(Entity::ID));
        $bankAccount = (new BankAccount\Core)->fetch($this->input->get(Entity::BANK_ACCOUNT_ID));

        $this->gatewayInput->put(Entity::VPA, $vpa);
        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);

        return $this->callGateway();
    }

    public function assignBankAccountSuccess(array $input): array
    {
        $this->initialize(Action::ASSIGN_BANK_ACCOUNT_SUCCESS, $input, true);

        // Since we have same handle in context, we can assure that both
        // the vpa and bank account belongs to same handle
        $vpa         = $this->core->fetch($this->input->get(Entity::VPA)[Entity::ID]);
        $bankAccount = (new BankAccount\Core)->fetch($this->input->get(Entity::BANK_ACCOUNT)[Entity::ID]);

        $this->core->assignBankAccount($vpa, $bankAccount);

        return $vpa->toArrayPublic();
    }

    public function checkAvailability(array $input): array
    {
        $this->initialize(Action::CHECK_AVAILABILITY, $input, true);

        if ($this->core->checkLocalAvailability($this->input->get(Entity::USERNAME)))
        {
            throw new \Exception('Change the exception and message');
        }

        $this->gatewayInput->put(Entity::USERNAME, $this->input->get(Entity::USERNAME));

        return $this->callGateway();
    }

    public function checkAvailabilitySuccess(array $input): array
    {
        $this->initialize(Action::CHECK_AVAILABILITY_SUCCESS, $input, true);

        return [
            Entity::SUCCESS     => true,
            Entity::USERNAME    => $this->input->get(Entity::VPA)[Entity::USERNAME],
            Entity::HANDLE      => $this->input->get(Entity::VPA)[Entity::HANDLE],
        ];
    }

    public function delete(array $input): array
    {
        $this->initialize(Action::DELETE, $input, true);

        $vpa = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->put(Entity::VPA, $vpa);

        return $this->callGateway();
    }

    public function deleteSuccess(array $input): array
    {
        $this->initialize(Action::DELETE_SUCCESS, $input, true);

        $vpa = $this->core->fetch($this->input->get(Entity::VPA)[Entity::ID]);

        return [
            Entity::SUCCESS     => true,
            Entity::ID          => $vpa->getPublicId(),
        ];
    }
}
