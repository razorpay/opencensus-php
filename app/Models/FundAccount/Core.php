<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Exception\LogicException;

/**
 * Class Core
 *
 * @package RZP\Models\FundAccount
 */
class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant, Base\PublicEntity $source = null): Entity
    {
        $fundAccount = (new Entity)->build($input);

        $this->repo->transaction(function() use ($input, $merchant, $source, $fundAccount) {
            $account = $this->createAccount($input, $source);

            $fundAccount->merchant()->associate($merchant);

            $fundAccount->source()->associate($source);

            $fundAccount->account()->associate($account);

            $this->repo->saveOrFail($fundAccount);
        });

        return $fundAccount;
    }

    protected function createAccount(array $input, Base\PublicEntity $source): Base\PublicEntity
    {
        $accountType = $input[Entity::ACCOUNT_TYPE];

        $accountInput = $input[Entity::DETAILS];

        $account = null;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $account = (new BankAccount\Core)->createBankAccountForBankingSource($accountInput, $source);
                break;

            case Type::VPA:
                // TODO

            default:
                throw new LogicException('Creation logic not defined for fund account type: ' . $accountType);
        }

        return $account;
    }

    public function update(Entity $fundAccount, array $input): Entity
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_UPDATE_REQUEST,
            [
                'id'     => $fundAccount->getId(),
                'entity' => $fundAccount->toArray(),
                'input'  => $input,
            ]);

        $fundAccount->edit($input);

        $this->repo->saveOrFail($fundAccount);

        return $fundAccount;
    }

    public function delete(Entity $fundAccount)
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_DELETE_REQUEST, ['id' => $fundAccount->getId()]);

        return $this->repo->deleteOrFail($fundAccount);
    }
}
