<?php

namespace Models\Merchant;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

    protected $entity = 'Merchant';

    public function getBalanceLockForUpdate($id)
    {
        return Merchant\Balance::lockForUpdate()->findOrFail($id);
    }

    public function getMerchantBalanceLockForUpdate($merchant)
    {
        return $this->getBalanceLockForUpdate($merchant->getKey());
    }

    public function getMerchantBalance($merchant)
    {
        return Merchant\Balance::findOrFailPublic($merchant->getId());
    }

    public function updateBalance($balance)
    {
        $balance->saveOrFail();
    }

    public function updateBankAccount($ba)
    {
        $ba->saveOrFail();
    }

    public function getBankAccount($merchant)
    {
        $repo = 'Models\Merchant\BankAccount';

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
        $repo = 'Models\Merchant\BankAccount';

        return $repo::where(BankAccount::BENEFICIARY_CODE, '=', $code)->first();
    }

    public function getBeneficiaryCodeCountByPattern($code)
    {
        $repo = 'Models\Merchant\BankAccount';

        return $repo::where(BankAccount::BENEFICIARY_CODE, 'like', $code.'%')->count();
    }

    public function getEscrowBalanceLockForUpdate($channel)
    {
        $func = 'get'.ucfirst($channel).'BalanceLockForUpdate';

        return $this->$func();
    }

    public function getKotakBalanceLockForUpdate()
    {
        return $this->getBalanceLockForUpdate(Merchant\Account::NODAL_ACCOUNT);
    }

    public function getAtomBalanceLockForUpdate()
    {
        return $this->getBalanceLockForUpdate(Merchant\Account::ATOM_ACCOUNT);
    }

    public function getPricingPlanOrFailPublic($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return $pricing;
    }

    public function fetchMerchantsWithPositiveBalance()
    {
        $repo = $this->repo;

        return $repo::whereHas('balance', function($q)
        {
            $q->where('balance', '>', 0);
        })->get();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}