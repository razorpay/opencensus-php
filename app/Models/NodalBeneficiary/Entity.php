<?php

namespace RZP\Models\NodalBeneficiary;

use RZP\Models\Base;

use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const CHANNEL             = 'channel';
    const MERCHANT_ID         = 'merchant_id';
    const BANK_ACCOUNT_ID     = 'bank_account_id';
    const BENEFICIARY_CODE    = 'beneficiary_code';
    const REGISTRATION_STATUS = 'registration_status';

    protected $entity = 'nodal_beneficiary';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::CHANNEL,
        self::BENEFICIARY_CODE,
        self::REGISTRATION_STATUS
    ];

    protected $defaults = [
        self::REGISTRATION_STATUS => Status::CREATED,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity');
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
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

    public function getRegistrationStatus()
    {
        return $this->getAttribute(self::REGISTRATION_STATUS);
    }
}
