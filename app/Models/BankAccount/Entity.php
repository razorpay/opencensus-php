<?php

namespace RZP\Models\BankAccount;

use App;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;
use RZP\Models\VirtualAccount;
use RZP\Exception;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const ENTITY_ID                 = 'entity_id';
    const TYPE                      = 'type';
    const BENEFICIARY_CODE          = 'beneficiary_code';
    const IFSC_CODE                 = 'ifsc_code';
    const ACCOUNT_NUMBER            = 'account_number';
    const BENEFICIARY_NAME          = 'beneficiary_name';
    const BENEFICIARY_ADDRESS1      = 'beneficiary_address1';
    const BENEFICIARY_ADDRESS2      = 'beneficiary_address2';
    const BENEFICIARY_ADDRESS3      = 'beneficiary_address3';
    const BENEFICIARY_ADDRESS4      = 'beneficiary_address4';
    const BENEFICIARY_EMAIL         = 'beneficiary_email';
    const BENEFICIARY_MOBILE        = 'beneficiary_mobile';
    const BENEFICIARY_PIN           = 'beneficiary_pin';
    const BENEFICIARY_CITY          = 'beneficiary_city';
    const BENEFICIARY_STATE         = 'beneficiary_state';
    const BENEFICIARY_COUNTRY       = 'beneficiary_country';
    const DELETED_AT                = 'deleted_at';
    const MOBILE_BANKING_ENABLED    = 'mobile_banking_enabled';
    const MPIN                      = 'mpin';

    const NAME                      = 'name';
    const IFSC                      = 'ifsc';

    // Mobile Banking Enabled
    const MPIN_SET              = 'mpin_set';

    const IFSC_CODE_LENGTH      = 11;

    const SPECIAL_IFSC_CODE     = 'RZPB0000000';

    protected static $sign      = 'ba';

    protected $primaryKey = self::ID;

    protected $entity = 'bank_account';

    protected $fillable = [
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::TYPE,
        self::IFSC_CODE,
        self::MOBILE_BANKING_ENABLED,
        self::BENEFICIARY_NAME,
        self::ACCOUNT_NUMBER,
        self::BENEFICIARY_ADDRESS1,
        self::BENEFICIARY_ADDRESS2,
        self::BENEFICIARY_ADDRESS3,
        self::BENEFICIARY_ADDRESS4,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_CITY,
        self::BENEFICIARY_STATE,
        self::BENEFICIARY_PIN,
    ];

    protected $visible = [
        self::ID,
        self::IFSC,
        self::IFSC_CODE,
        self::NAME,
        self::BENEFICIARY_NAME,
        self::ACCOUNT_NUMBER,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::TYPE,
        self::BENEFICIARY_CODE,
        self::BENEFICIARY_ADDRESS1,
        self::BENEFICIARY_ADDRESS2,
        self::BENEFICIARY_ADDRESS3,
        self::BENEFICIARY_ADDRESS4,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_CITY,
        self::BENEFICIARY_STATE,
        self::BENEFICIARY_COUNTRY,
        self::BENEFICIARY_PIN,
        self::MPIN_SET,
        self::MPIN,
        self::MOBILE_BANKING_ENABLED,
        self::CREATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::IFSC,
        self::NAME,
        self::ACCOUNT_NUMBER,
    ];

    protected $appends = [
        self::NAME,
        self::IFSC,
        self::MPIN_SET,
    ];

    protected $guarded = [self::ID];

    protected static $generators = [
        self::ID,
        self::BENEFICIARY_COUNTRY,
    ];

    protected $casts = [
        self::MOBILE_BANKING_ENABLED => 'bool',
    ];

    protected $generateIdOnCreate = true;

    public function build(array $input = [], string $operation = 'addBankAccount')
    {
        $this->getValidator()->validateInput($operation, $input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

    // we are not doing it via generators as we want to generate only for
    // merchant bank accounts
    public function generateBeneficiaryCode()
    {
        $beneficiaryCode = $this->getKotakBeneficaryCode();

        $this->setAttribute(self::BENEFICIARY_CODE, $beneficiaryCode);
    }

    protected function generateBeneficiaryCountry($input)
    {
        $this->setAttribute(self::BENEFICIARY_COUNTRY, 'IN');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function vpa()
    {
        return $this->hasOne('RZP\Models\Upi\Vpa\Entity');
    }

    public function source()
    {
        $type = $this->getAttribute(self::TYPE);

        Type::validateType($type);

        $class = Type::getEntityClass($type);

        return $this->belongsTo($class, self::ENTITY_ID);
    }

    public function payouts()
    {
        return $this->morphMany('RZP\Models\Payout\Entity', 'destination');
    }

    public function getMpinSetAttribute()
    {
        return ($this->getAttribute(self::MPIN) !== null);
    }

    protected function getMpinAttribute()
    {
        if (isset($this->attributes[self::MPIN]) === false)
        {
            return null;
        }

        $mpin = $this->attributes[self::MPIN];

        return Crypt::decrypt($mpin);
    }

    protected function getNameAttribute()
    {
        return $this->attributes[self::BENEFICIARY_NAME];
    }

    public function settlements()
    {
        return $this->hasMany('RZP\Models\Settlement\Entity');
    }

    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getIfscCode()
    {
        return $this->getAttribute(self::IFSC_CODE);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getBeneficiaryCode()
    {
        return $this->getAttribute(self::BENEFICIARY_CODE);
    }

    public function getMpin()
    {
        return $this->getAttribute(self::MPIN);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getMobileBankingEnabled()
    {
        return $this->getAttribute(self::MOBILE_BANKING_ENABLED);
    }

    public function setMobileBankingEnabled($mobileBankingEnabled)
    {
        return $this->setAttribute(self::MOBILE_BANKING_ENABLED, $mobileBankingEnabled);
    }

    public function setMpin($mpin)
    {
        return $this->setAttribute(self::MPIN, $mpin);
    }

    protected function setIfscCodeAttribute($code)
    {
        if ($code !== null)
        {
            $code = strtoupper($code);
        }

        $this->attributes[self::IFSC_CODE] = $code;
    }

    protected function setMpinAttribute($mpin)
    {
        if ($mpin === null)
        {
            $mpin = '';
        }

        $this->attributes[self::MPIN] = Crypt::encrypt($mpin);
    }

    protected function getIfscCodeAttribute()
    {
        $code = $this->attributes[self::IFSC_CODE];

        if ($code !== null)
        {
            $code = strtoupper($code);
        }

        return $code;
    }

    protected function getIfscAttribute()
    {
        return $this->attributes[self::IFSC_CODE];
    }

    public function equals($baCopy)
    {
        $orig = $this->toArray();
        ksort($orig);
        unset(
            $orig[self::ID],
            $orig[self::CREATED_AT],
            $orig[self::UPDATED_AT],
            $orig[self::DELETED_AT],
            $orig[self::BENEFICIARY_ADDRESS4]);

        $copy = $baCopy->toArray();
        ksort($copy);
        unset(
            $copy[self::ID],
            $copy[self::CREATED_AT],
            $copy[self::UPDATED_AT],
            $copy[self::DELETED_AT],
            $copy[self::BENEFICIARY_ADDRESS4]);

        return ($orig === $copy);
    }

    /**
     * Kotak requires a beneficiary code in 10 characters.
     * Beneficiary code is generated by using the first 7 + last 3 of
     * Bank account number id.
     *
     * @return string Beneficiary code in 10 characters.
     */
    protected function getKotakBeneficaryCode()
    {
        $id = $this->getAttribute(self::ID);

        $first7 = substr($id, 0, 7);

        $last3 = substr($id, -3);

        $beneficiaryCode = $first7 . $last3;

        $beneficiaryCode = strtoupper($beneficiaryCode);

        assertTrue(strlen($beneficiaryCode) === 10);

        return $beneficiaryCode;
    }

    public function associateCustomer($customer)
    {
        $this->attributes[self::ENTITY_ID] = $customer->getId();

        $this->attributes[self::TYPE] = Type::CUSTOMER;
    }

    public function associateMerchant($merchant)
    {
        $this->attributes[self::ENTITY_ID] = $merchant->getId();

        $this->attributes[self::TYPE] = Type::MERCHANT;
    }

    public function associateVirtualAccount(VirtualAccount\Entity $virtualAccount)
    {
        $this->attributes[self::TYPE] = Type::VIRTUAL_ACCOUNT;

        $this->source()->associate($virtualAccount);
    }

    public function getRedactedAccountNumber()
    {
        $ac = $this->getAccountNumber();
        //
        // How many times should we repeat the redacted portion
        // This does not give a precise result,
        // but it looks good in groups of 4
        //
        // (strlen($ac) - 4) = Length of the segment we want to convert to X
        // divide by 4 to get number of such segments
        // and take ceil so we have a whole number of these

        $repeat = ceil((strlen($ac) - 4) / 4);

        // repeat this section $repeat times
        // and then just append the original last 4 digits
        return str_repeat('XXXX-', $repeat) . substr($ac, -4);
    }
}
