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

        foreach ($bankAccounts as $bankAccount)
        {
            $existing = $this->fetchExistingBankAccount($bankAccount, $existingBankAccounts);

            if (is_null($existing) === false)
            {
                $existing->setCreds($bankAccount[Entity::CREDS]);
                $existing->mergeGatewayData($bankAccount[Entity::GATEWAY_DATA]);

                $this->repo->saveOrFail($existing);
            }
            else
            {
                $this->createForBank($bankAccount, $bank);
            }
        }

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
}
