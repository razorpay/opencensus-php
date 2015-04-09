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

    public function updateBankAccount($ba)
    {
        $ba->saveOrFail();
    }

    public function getBankAccount($merchant)
    {
        $repo = $this->repo;

        $ba = $repo::find($merchant->getId());

        if ($ba !== null)
        {
            $ba->merchant()->associate($merchant);

            $merchant->setRelation('bankAccount', $ba);
        }

        return $ba;
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

    public function getAll()
    {
        $repo = $this->repo;

        return $repo::all();
    }
}

