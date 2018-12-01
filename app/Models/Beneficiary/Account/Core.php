<?php

namespace RZP\Models\Beneficiary\Account;

use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Beneficiary;
use RZP\Models\BankAccount;

/**
 * Class Core
 *
 * @package RZP\Models\Beneficiary\Account
 */
class Core extends Base\Core
{
    public function create(array $input, Beneficiary\Entity $beneficiary, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::BENEFICIARY_ACCOUNT_CREATE_REQUEST, ['input' => $input]);

        $beneAccount = (new Entity)->build();

        $this->repo->transaction(function() use ($input, $merchant, $beneficiary, $beneAccount)
        {
            $account = $this->createAccount($input, $beneficiary);

            $beneAccount->merchant()->associate($merchant);

            $beneAccount->beneficiary()->associate($beneficiary);

            $beneAccount->account()->associate($account);

            $this->repo->saveOrFail($beneAccount);
        });

        return $beneAccount;
    }

    public function createAccount(array & $input, Beneficiary\Entity $beneficiary): Base\PublicEntity
    {
        $accountType = array_pull($input, Entity::ACCOUNT_TYPE);

        $accountInput = array_pull($input, $accountType);

        $account = null;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $account = (new BankAccount\Core)->createBankAccountForBankingBeneficiary($accountInput, $beneficiary);
                break;

            default:
                throw new LogicException('Temp');
        }

        return $account;
    }

    public function update(Entity $beneAccount, array $input): Entity
    {
        $this->trace->info(
            TraceCode::BENEFICIARY_ACCOUNT_UPDATE_REQUEST,
            [
                'id'    => $beneAccount->getId(),
                'input' => $input,
            ]);

        $beneAccount->edit($input);

        $this->repo->saveOrFail($beneAccount);

        return $beneAccount;
    }

    public function delete(Entity $beneAccount)
    {
        $this->trace->info(TraceCode::BENEFICIARY_ACCOUNT_DELETE_REQUEST, ['id' => $beneAccount->getId()]);

        return $this->repo->deleteOrFail($beneAccount);
    }
}
