<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Events\P2p;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Transaction;
use RZP\Models\P2p\Device\DeviceToken;
use RZP\Exception\P2p\BadRequestException;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    use Base\Traits\FetchTrait;

    public function initiateAdd(array $input): array
    {
        $this->initialize(Action::INITIATE_ADD, $input, true);

        $bankAccount = (new BankAccount\Core)->find($this->input->get(Entity::BANK_ACCOUNT_ID));

        $username = $this->input->get(Entity::USERNAME);

        if (empty($username) === true)
        {
            $username = $this->core->suggestUsername($bankAccount);
        }

        $this->runUsernameValidationChecks($username);

        $this->runUsernameLocalValidationCheck($username);

        $this->gatewayInput->put(Entity::USERNAME, $username);
        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);

        $this->callbackInput->put(Entity::DATA, [
            Entity::USERNAME            => $username,
            Entity::BANK_ACCOUNT_ID     => $bankAccount->getPublicId(),
        ]);

        return $this->callGateway();
    }

    public function add(array $input): array
    {
        $this->initialize(Action::ADD, $input, true);

        $username = $this->input->get(Entity::USERNAME);

        $this->runUsernameValidationChecks($username);

        $this->runUsernameLocalValidationCheck($username);

        $this->gatewayInput->put(Entity::USERNAME, $username);

        $bankAccount = (new BankAccount\Core)->find($this->input->get(Entity::BANK_ACCOUNT_ID));

        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);

        $this->callbackInput->put(Entity::DATA, [
            Entity::USERNAME            => $username,
            Entity::BANK_ACCOUNT_ID     => $this->input->get(Entity::BANK_ACCOUNT_ID),
        ]);

        return $this->callGateway();
    }

    protected function addSuccess(array $input): array
    {
        $this->initialize(Action::ADD_SUCCESS, $input, true);

        $bankAccountId = array_get($this->input->get(Entity::BANK_ACCOUNT), Entity::ID);
        $bankAccount   = null;

        if (is_null($bankAccountId) === false)
        {
            $bankAccount = (new BankAccount\Core)->fetch($bankAccountId);
        }

        $vpa = $this->repo()->transaction(function() use ($bankAccount)
        {
            $vpa = $this->core->createOrUpdate($this->input->get(Entity::VPA));

            if (is_null($bankAccount) === false)
            {
                $this->core->assignBankAccount($vpa, $bankAccount);
            }

            $this->handleDeviceTokenIfApplicable();

            return $vpa;
        });

        $this->app['events']->dispatch(new P2p\VpaCreated($this->context(), $vpa));

        return $vpa->toArrayPublic();
    }

    public function assignBankAccount(array $input): array
    {
        $this->initialize(Action::ASSIGN_BANK_ACCOUNT, $input, true);

        // Since we have same handle in context, we can assure that both
        // the vpa and bank account belongs to same handle
        $vpa         = $this->core->fetch($this->input->get(Entity::ID), true);
        $bankAccount = (new BankAccount\Core)->fetch($this->input->get(Entity::BANK_ACCOUNT_ID));

        $this->gatewayInput->put(Entity::VPA, $vpa);
        $this->gatewayInput->put(Entity::BANK_ACCOUNT, $bankAccount);

        $this->callbackInput->put(Entity::DATA, [
            Entity::BANK_ACCOUNT_ID     => $this->input->get(Entity::BANK_ACCOUNT_ID),
        ]);

        $this->callbackInput->put('vpa_id', $this->input->get(Entity::ID));

        return $this->callGateway();
    }

    protected function assignBankAccountSuccess(array $input): array
    {
        $this->initialize(Action::ASSIGN_BANK_ACCOUNT_SUCCESS, $input, true);

        // Since we have same handle in context, we can assure that both
        // the vpa and bank account belongs to same handle
        $vpa         = $this->core->fetch($this->input->get(Entity::VPA)[Entity::ID], true);
        $bankAccount = (new BankAccount\Core)->fetch($this->input->get(Entity::BANK_ACCOUNT)[Entity::ID]);

        $this->core->assignBankAccount($vpa, $bankAccount);

        $this->handleDeviceTokenIfApplicable();

        return $vpa->toArrayPublic();
    }

    public function setDefault(array $input): array
    {
        $this->initialize(Action::SET_DEFAULT, $input, true);

        $vpa = $this->core->fetch($this->input->get(Entity::ID));

        if ($vpa->isDefault() === true)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $defaultVpa = $this->core->getDefaultVpa();

        $this->gatewayInput->put(Entity::VPA, $vpa);
        $this->gatewayInput->put(Entity::DEFAULT, $defaultVpa);

        return $this->callGateway();
    }

    protected function setDefaultSuccess(array $input): array
    {
        $this->initialize(Action::SET_DEFAULT_SUCCESS, $input, true);

        $vpa = $this->core->fetch($this->input->get(Entity::VPA)[Entity::ID]);

        $vpa = $this->repo()->transaction(function() use ($vpa)
        {
            return $this->core->setDefaultVpa($vpa);
        });

        return $vpa->toArrayPublic();
    }

    public function initiateCheckAvailability(array $input): array
    {
        $this->initialize(Action::INITIATE_CHECK_AVAILABILITY, $input, true);

        $username = $this->input->get(Entity::USERNAME);

        $this->runUsernameValidationChecks($username);

        $this->gatewayInput->put(Entity::USERNAME, $username);

        $this->callbackInput->put(Entity::DATA, [
            Entity::USERNAME     => $username,
        ]);

        return $this->callGateway();
    }

    public function checkAvailability(array $input): array
    {
        $this->initialize(Action::CHECK_AVAILABILITY, $input, true);

        $username = $this->input->get(Entity::USERNAME);

        $this->runUsernameValidationChecks($username);

        $this->gatewayInput->put(Entity::USERNAME, $username);

        return $this->callGateway();
    }

    protected function checkAvailabilitySuccess(array $input): array
    {
        $this->initialize(Action::CHECK_AVAILABILITY_SUCCESS, $input, true);

        return array_only($this->input->toArray(),[
            Entity::AVAILABLE,
            Entity::USERNAME,
            Entity::HANDLE,
            Entity::SUGGESTIONS
        ]);
    }

    public function delete(array $input): array
    {
        $this->initialize(Action::DELETE, $input, true);

        $vpa = $this->core->fetch($this->input->get(Entity::ID));

        if ($vpa->isDefault() === true)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $defaultVpa = $this->core->getDefaultVpa();

        $this->gatewayInput->put(Entity::VPA, $vpa);
        $this->gatewayInput->put(Entity::DEFAULT, $defaultVpa);

        return $this->callGateway();
    }

    protected function deleteSuccess(array $input): array
    {
        $this->initialize(Action::DELETE_SUCCESS, $input, true);

        $vpa = $this->core->fetch($this->input->get(Entity::VPA)[Entity::ID]);

        $this->repo()->transaction(function() use ($vpa)
        {
            (new Transaction\Core)->deletePendingCollectForVpa($vpa);

            $this->core->removeBankAccount($vpa);

            $this->core->delete($vpa);
        });

        $this->app['events']->dispatch(new P2p\VpaDeleted($this->context(), $vpa));

        return [
            Entity::SUCCESS     => true,
            Entity::ID          => $vpa->getPublicId(),
        ];
    }

    protected function runUsernameValidationChecks(string $username)
    {
        if ($this->core->checkForMaxVpaLimit())
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_MAX_VPA_LIMIT_REACHED, [
                Entity::USERNAME    => $username,
            ]);
        }

        if ($this->core->checkUsernameBlocked($username))
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_VPA_NOT_AVAILABLE, [
                Entity::USERNAME    => $username,
            ]);
        }
    }

    protected function runUsernameLocalValidationCheck(string $username)
    {
        if ($this->core->checkLocalAvailability($username))
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_VPA, [
                Entity::USERNAME    => $username,
            ]);
        }
    }

    protected function handleDeviceTokenIfApplicable()
    {
        if ($this->input->has(DeviceToken\Entity::DEVICE_TOKEN))
        {
            $id = $this->input->get(DeviceToken\Entity::DEVICE_TOKEN)[DeviceToken\Entity::ID];

            $core = (new DeviceToken\Core);
            $deviceToken = $core->fetch($id);
            $core->update($deviceToken, $this->input->get(DeviceToken\Entity::DEVICE_TOKEN));
        }
    }
}
