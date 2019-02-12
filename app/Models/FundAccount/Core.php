<?php

namespace RZP\Models\FundAccount;

use RZP\Exception;
use RZP\Models\Vpa;
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
    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param Base\PublicEntity|null $source
     * @return Entity
     * @throws Exception\BaseException
     */
    public function create(array $input, Merchant\Entity $merchant, Base\PublicEntity $source = null): Entity
    {
        $this->modifyValidationRequestToOldFormat($input);

        $fundAccount = (new Entity)->build($input);

        $this->repo->transaction(function() use ($input, $merchant, $source, $fundAccount) {
            $account = $this->createAccount($input, $merchant, $source);

            $fundAccount->merchant()->associate($merchant);

            $fundAccount->source()->associate($source);

            $fundAccount->account()->associate($account);

            $this->repo->saveOrFail($fundAccount);
        });

        return $fundAccount;
    }

    protected function modifyValidationRequestToOldFormat(array & $input)
    {
        if (isset($input[Validation\FundAccountType::BANK_ACCOUNT]))
        {
            $input[Entity::DETAILS] = $input[Validation\FundAccountType::BANK_ACCOUNT];

            unset($input[Validation\FundAccountType::BANK_ACCOUNT]);
        }
    }

    protected function createAccount(array $input,
                                     Merchant\Entity $merchant,
                                     Base\PublicEntity $source = null): Base\PublicEntity
    {
        $accountType = $input[Entity::ACCOUNT_TYPE];

        $accountInput = $input[Entity::DETAILS];

        $account = null;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $account = (new BankAccount\Core)->createBankAccountForFundAccount($accountInput, $merchant, $source);
                break;

            case Type::VPA:
                $account = (new Vpa\Core)->createForBankingSource($accountInput, $source);
                break;

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

    /**
     * @param string $id
     * @param Merchant\Entity $merchant
     * @return Entity
     * @throws Exception\BaseException, if id is not found
     */
    public function findByPublicIdAndMerchant(string $id, Merchant\Entity $merchant): Entity
    {
        return $this->repo->fund_account->findByPublicIdAndMerchant($id, $merchant);
    }
}
