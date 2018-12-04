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
        $fundAccount = (new Entity)->build();

        $this->repo->transaction(function() use ($input, $merchant, $contact, $fundAccount) {
            $account = $this->createAccount($input, $contact);

            $fundAccount->merchant()->associate($merchant);

            $fundAccount->contact()->associate($contact);

            $fundAccount->account()->associate($account);

            $this->repo->saveOrFail($fundAccount);
        });

        return $fundAccount;
    }

    protected function createAccount(array & $input, Contact\Entity $contact): Base\PublicEntity
    {
        //
        // We want the account_type attribute to be filled in by association, and not via input
        // hence, we remove this from the input array.
        //
        $accountType = array_pull($input, Entity::ACCOUNT_TYPE);

        //
        // The `details` object is passed on as input to the create function of the respective
        // account type (bank account, vpa, etc). This is not required
        //
        $accountInput = array_pull($input, Entity::DETAILS);

        $account = null;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $account = (new BankAccount\Core)->createBankAccountForBankingContact($accountInput, $contact);
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
