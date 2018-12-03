<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Models\Contact;
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
    public function create(array $input, Merchant\Entity $merchant, Contact\Entity $contact = null): Entity
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST, ['input' => $input]);

        $fundAccount = (new Entity)->build();

        $this->repo->transaction(function() use ($input, $merchant, $contact, $fundAccount)
        {
            $account = $this->createAccount($input, $contact);

            $fundAccount->merchant()->associate($merchant);

            $fundAccount->contact()->associate($contact);

            $fundAccount->account()->associate($account);

            $this->repo->saveOrFail($fundAccount);
        });

        return $fundAccount;
    }

    public function createAccount(array & $input, Contact\Entity $contact): Base\PublicEntity
    {
        $accountType = array_pull($input, Entity::ACCOUNT_TYPE);

        $accountInput = array_pull($input, $accountType);

        $account = null;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $account = (new BankAccount\Core)->createBankAccountForBankingContact($accountInput, $contact);
                break;

            default:
                throw new LogicException('Temp');
        }

        return $account;
    }

    public function update(Entity $fundAccount, array $input): Entity
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_UPDATE_REQUEST,
            [
                'id'    => $fundAccount->getId(),
                'input' => $input,
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
