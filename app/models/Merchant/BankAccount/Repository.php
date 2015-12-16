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

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
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

    public function getBeneficiaryCodeCountByPattern($code)
    {
        $repo = $this->repo;

        return $repo::where(BankAccount\Entity::BENEFICIARY_CODE, 'like', $code.'%')->count();
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

    public function bankAccountsWhereIdNullOrBlank()
    {
        return $repo::where(Entity::ID, '=', "")
                                ->orWhereNull(BankAccount\Entity::ID)
                                ->take(500)
                                ->get();
    }
}
