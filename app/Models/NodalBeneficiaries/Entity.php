<?php

namespace RZP\Models\NodalBeneficiaries;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const STATUS           = 'status';
    const NODAL_BANK       = 'nodal_bank';
    const MERCHANT_ID      = 'merchant_id';
    const BANK_ACCOUNT_ID  = 'bank_account_id';
    const BENEFICIARY_CODE = 'beneficiary_code';

    protected $entity = 'nodal_beneficiaries';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::STATUS,
        self::NODAL_BANK,
        self::MERCHANT_ID,
        self::BANK_ACCOUNT_ID,
        self::BENEFICIARY_CODE
    ];

    protected $public = [
        self::ID,
        self::STATUS,
        self::NODAL_BANK,
        self::MERCHANT_ID,
        self::BANK_ACCOUNT_ID,
        self::BENEFICIARY_CODE
    ];

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getNodalBank()
    {
        return $this->getAttribute(self::NODAL_BANK);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getBankAccountId()
    {
        return $this->getAttribute(self::BANK_ACCOUNT_ID);
    }

    public function geBeneficiaryCode()
    {
        return $this->getAttribute(self::BENEFICIARY_CODE);
    }

}
