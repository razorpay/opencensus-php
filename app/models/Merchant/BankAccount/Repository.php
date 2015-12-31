<?php

namespace Models\Merchant\BankAccount;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Merchant\BankAccount;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'BankAccount';

    const WITH_TRASHED = 'with_trashed';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        self::WITH_TRASHED      => 'sometimes|in:0,1',
    );

    public function updateBankAccount($ba)
    {
        $ba->saveOrFail();
    }

    public function getBankAccount($merchant)
    {
        return $merchant->bankAccount;
    }

    public function getBankAccountByBeneficiaryCode($code)
    {
        $repo = $this->repo;

        return $repo::where(BankAccount\Entity::BENEFICIARY_CODE, '=', $code)->first();
    }

    public function getBeneficiaryCodeCountByPattern($code, $mode)
    {
        $repo = $this->repo;

        $highest = $repo::on($mode)
                    ->withTrashed()
                    ->where(BankAccount\Entity::BENEFICIARY_CODE, 'like', $code.'%')
                    ->orderBy(BankAccount\Entity::BENEFICIARY_CODE, 'desc')
                    ->first();

        if ($highest === null)
        {
            return 0;
        }

        $count = substr($highest->getBeneficiaryCode(), 4);

        // The first entry doesn't have any count
        if ($count === false)
        {
            $count = 1;
        }

        return $count;
    }

    public function getAllOrderedByCreatedAt()
    {
        $repo = $this->repo;

        return $repo::query()->orderBy(BankAccount\Entity::CREATED_AT)->get();
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }

    protected function addQueryParamWithTrashed($query, $params)
    {
        if ($params[self::WITH_TRASHED] === '1')
        {
            $query->withTrashed();
        }
    }

    public function bankAccountsWhereIdNullOrBlank()
    {
        $repo = $this->repo;

        return $repo::where(Entity::ID, '=', '')
                                ->orWhereNull(BankAccount\Entity::ID)
                                ->take(500)
                                ->get();
    }

    /**
     * This should be called when deleting a BankAccount Entity.
     *
     * This checks if the bankAccount has any settlements linked to it.
     * If there are linked settlements then it is soft deleted.
     * Else, it is hard deleted.
     *
     * @param  BankAccount\Entity $bankAccount The bank account to be deleted
     */
    public function delete($bankAccount)
    {
        if ($bankAccount->settlements->count() === 0)
        {
            return $bankAccount->forceDelete();
        }
        else
        {
            return $bankAccount->delete();
        }
    }
}
