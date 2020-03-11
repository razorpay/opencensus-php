<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Base\Upi;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function initiateRetrieve(array $input): array
    {
        $this->initialize(Action::INITIATE_RETRIEVE, $input, true);

        $bank = (new Bank\Core)->fetch($this->input->get(Entity::BANK_ID));

        $this->gatewayInput->put(Entity::BANK, $bank->toArrayBag());

        $this->callbackInput->push($bank->getPublicId());

        return $this->callGateway();
    }

    public function retrieve(array $input): array
    {
        $this->initialize(Action::RETRIEVE, $input, true);

        $bank = (new Bank\Core)->find($this->input->get(Entity::BANK_ID));

        $this->gatewayInput->put(Entity::BANK, $bank->toArrayBag());

        return $this->callGateway();
    }

    protected function retrieveSuccess(array $input): array
    {
        $this->initialize(Action::RETRIEVE_SUCCESS, $input, true);

        $bank = (new Bank\Core)->fetch($this->input->get(Entity::BANK_ID));

        $bankAccounts = $this->core->createManyForBank($this->input->get(Entity::BANK_ACCOUNTS), $bank);

        if ($bankAccounts->count() === 0)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_NO_BANK_ACCOUNT_FOUND);
        }

        return $bankAccounts->toArrayPublic();
    }

    public function initiateSetUpiPin(array $input): array
    {
        $this->initialize(Action::INITIATE_SET_UPI_PIN, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        $vpa = (new Vpa\Core)->getAttachedVpa($bankAccount);

        if (empty($vpa) === true)
        {
            throw new \Exception('Is should be attached');
        }

        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);
        $this->gatewayInput->put(Vpa\Entity::VPA, $vpa);

        $this->gatewayInput->putMany($this->input->only([
            Entity::ACTION,
            Base\Libraries\Card::CARD,
        ])->toArray());

        $this->callbackInput->push($bankAccount->getPublicId());

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

        return $this->callGateway();
    }

    public function setUpiPinSuccess(array $input): array
    {
        $this->initialize(Action::SET_UPI_PIN_SUCCESS, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        $bankAccount->setCredsUpiPin(true);

        $this->core->update($bankAccount, $this->input->except(Entity::ID)->toArray());

        return $bankAccount->toArrayPublic();
    }

    public function setUpiPinFailure(array $input): array
    {
        // This is just to validate the data update in case of failure
        // Not being used any where but for sharp testing
        // Thus no validator are required for this
        $this->initialize(Action::SET_UPI_PIN_FAILURE, $input);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        $bankAccount->mergeGatewayData($this->input->get(Entity::GATEWAY_DATA));

        $this->core->update($bankAccount, []);

        return $bankAccount->toArrayPublic();
    }

    public function initiateFetchBalance(array $input): array
    {
        $this->initialize(Action::INITIATE_FETCH_BALANCE, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);

        $this->callbackInput->push($bankAccount->getPublicId());

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

        return $this->callGateway();
    }

    public function fetchBalanceSuccess(array $input): array
    {
        $this->initialize(Action::FETCH_BALANCE_SUCCESS, $input, true);

        $bankAccount = $this->core->fetch($this->input->get(Entity::ID));

        return [
            Entity::SUCCESS     => true,
            Entity::BALANCE     => $this->input->get(Entity::RESPONSE)[Entity::BALANCE],
            Entity::CURRENCY    => $this->input->get(Entity::RESPONSE)[Entity::CURRENCY],
            Entity::ID          => $bankAccount->getPublicId(),
        ];
    }
}
