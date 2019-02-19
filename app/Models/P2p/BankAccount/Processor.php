<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Upi;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function retrieve(array $input): array
    {
        $this->initialize(Action::RETRIEVE, $input, true);

        $bank = (new Bank\Core)->retrieveById($this->input->get(Entity::BANK));

        $this->gatewayInput->put(Entity::BANK, $bank->toArrayBag());

        return $this->callGateway();
    }

    protected function retrieveSuccess(array $input): array
    {
        $this->initialize(Action::RETRIEVE_SUCCESS, $input, true);

        $bank = (new Bank\Core)->retrieveById($this->input->get(Entity::BANK));

        $bankAccounts = $this->core->createManyForBank($this->input->get(Entity::BANK_ACCOUNTS), $bank);

        return $bankAccounts->toArrayPublic();
    }

    public function initiateSetUpiPin(array $input): array
    {
        $this->initialize(Action::INITIATE_SET_UPI_PIN, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);

        return $this->callGateway();
    }

    public function initiateSetUpiPinSuccess(array $input)
    {
        $this->initialize(Action::INITIATE_SET_UPI_PIN_SUCCESS, $input, true);

        $txn         = new Upi\Txn($this->input->get(Upi\Txn::TXN));
        $device      = $this->context()->getDevice();
        $bankAccount = $this->core->fetch($this->input->get(Entity::BANK_ACCOUNT)[Entity::ID]);

        $clientLibrary = new Upi\ClientLibrary($this->context()->getDevice());

        $clientLibrary->setTxn($txn);
        $clientLibrary->setDevice($device);
        $clientLibrary->setBankAccount($bankAccount);

        return [
            Upi\ClientLibrary::CL => $clientLibrary->toArrayPublic(),
        ];
    }

    public function setUpiPin(array $input): array
    {
        $this->initialize(Action::SET_UPI_PIN, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);
        $this->gatewayInput->put(Entity::BANK, $bankAccount->parentBank);
        $this->gatewayInput->put(Entity::REQUEST, $this->input);

        return $this->callGateway();
    }

    public function setUpiPinSuccess(array $input): array
    {
        $this->initialize(Action::SET_UPI_PIN_SUCCESS, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::BANK_ACCOUNT)[Entity::ID]);

        return [
            Entity::SUCCESS => true,
            Entity::ID      => $bankAccount->getPublicId(),
        ];
    }

    public function initiateFetchBalance(array $input): array
    {
        $this->initialize(Action::INITIATE_FETCH_BALANCE, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);

        return $this->callGateway();
    }

    public function initiateFetchBalanceSuccess(array $input)
    {
        $this->initialize(Action::INITIATE_FETCH_BALANCE_SUCCESS, $input, true);

        $txn         = new Upi\Txn($this->input->get(Upi\Txn::TXN));
        $device      = $this->context()->getDevice();
        $bankAccount = $this->core->fetch($this->input->get(Entity::BANK_ACCOUNT)[Entity::ID]);

        $clientLibrary = new Upi\ClientLibrary($this->context()->getDevice());

        $clientLibrary->setTxn($txn);
        $clientLibrary->setDevice($device);
        $clientLibrary->setBankAccount($bankAccount);

        return [
            Upi\ClientLibrary::CL => $clientLibrary->toArrayPublic(),
        ];
    }

    public function fetchBalance(array $input): array
    {
        $this->initialize(Action::FETCH_BALANCE, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);
        $this->gatewayInput->put(Entity::BANK, $bankAccount->parentBank);
        $this->gatewayInput->put(Entity::REQUEST, $this->input);

        return $this->callGateway();
        return [
            'id'       => 'ba_AtIZbXUOTDp1ND',
            'balance'  => 2928200,
            'currency' => 'INR'
        ];
    }

    public function fetchBalanceSuccess(array $input): array
    {
        $this->initialize(Action::FETCH_BALANCE_SUCCESS, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::BANK_ACCOUNT)[Entity::ID]);

        return [
            Entity::SUCCESS     => true,
            Entity::BALANCE     => $this->input->get(Entity::RESPONSE)[Entity::BALANCE],
            Entity::CURRENCY    => $this->input->get(Entity::RESPONSE)[Entity::CURRENCY],
            Entity::ID          => $bankAccount->getPublicId(),
        ];
    }
}
