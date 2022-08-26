<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function createManyForBank(array $bankAccounts, Bank\Entity $bank): PublicCollection
    {
        $existingBankAccounts = $this->repo->fetchAllForBank($bank->getId());

        $toCreate = [];
        $toUpdate = [];
        $toDelete = $existingBankAccounts->keyBy(Entity::ID);

        foreach ($bankAccounts as $bankAccount)
        {
            $existing = $this->fetchExistingBankAccount($bankAccount, $existingBankAccounts);

            if ($existing instanceof Entity)
            {
                $existing->generateRefreshedAt();
                $existing->setCreds($bankAccount[Entity::CREDS]);
                $existing->mergeGatewayData($bankAccount[Entity::GATEWAY_DATA]);

                $toUpdate[] = $existing;

                $toDelete->forget($existing->getId());
            }
            else
            {
                $toCreate[] = $bankAccount;
            }
        }

        $this->repo->transaction(function() use ($bank, $toCreate, $toUpdate, $toDelete)
        {
            foreach ($toCreate as $create)
            {
                $this->createForBank($create, $bank);
            }

            foreach ($toUpdate as $update)
            {
                $this->repo->saveOrFail($update);
            }

            foreach ($toDelete as $delete)
            {
                $delete->deleteOrFail();
            }
        });

        return $this->repo->fetchAllForBank($bank->getId());
    }

    public function createForBank(array $input, Bank\Entity $bank)
    {
        $bankAccount = $this->repo->newP2pEntity();

        $bankAccount->build($input);

        $bankAccount->bank()->associate($bank);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    public function handleBeneficiary(array $input)
    {
        $input[Entity::IFSC] = strtoupper($input[Entity::IFSC]);

        $bankAccount = $this->repo->findByAccountDetails($input[Entity::ACCOUNT_NUMBER], $input[Entity::IFSC]);

        if ($bankAccount instanceof Entity)
        {
            if ($bankAccount->isBeneficiary() === false)
            {
                throw $this->logicException('Bank account has to be for beneficiary', $bankAccount->only([
                    Entity::ACCOUNT_NUMBER,
                    Entity::IFSC,
                    Entity::DEVICE_ID,
                ]));
            }

            return $bankAccount;
        }

        return $this->createBeneficiary($input);
    }

    public function createBeneficiary(array $input)
    {
        $bankAccount = $this->repo->getEntityObject();

        $bankAccount->buildBeneficiary($input);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    public function update(Entity $bankAccount, array $input)
    {
        $bankAccount->edit($input);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    public function delete()
    {
        return $this->repo->newP2pQuery()->delete();
    }

    public function deregister()
    {
        // As of now, we do not have to do anything with bank account on deregister
    }

    /**
     * @param array $bankAccount
     * @param PublicCollection $existing
     * @return Entity
     */
    protected function fetchExistingBankAccount(array $bankAccount, PublicCollection $existing)
    {
        $gatewayId = array_get($bankAccount, 'gateway_data.id');

        foreach ($existing as $item)
        {
            $existingGatewayId = array_get($item, 'gateway_data.id');

            if ($gatewayId === $existingGatewayId)
            {
                return $item;
            }
        }
    }

    /**
     * @param array $input
     * This is the method to find bank account by account number and ifsc
     * @return mixed
     */
    public function findByAccountDetails(array $input)
    {
        return $this->repo->findByAccountDetails($input[Entity::ACCOUNT_NUMBER], $input[Entity::IFSC]);
    }
}
