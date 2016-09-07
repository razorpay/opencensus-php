<?php

namespace RZP\Models\BankAccount;

use App;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;
use RZP\Exception;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const ENTITY_ID             = 'entity_id';
    const TYPE                  = 'type';
    const IFSC_CODE             = 'ifsc_code';
    const ACCOUNT_NUMBER        = 'account_number';
    const BENEFICIARY_NAME      = 'beneficiary_name';
    const BENEFICIARY_ADDRESS1  = 'beneficiary_address1';
    const BENEFICIARY_ADDRESS2  = 'beneficiary_address2';
    const BENEFICIARY_ADDRESS3  = 'beneficiary_address3';
    const BENEFICIARY_ADDRESS4  = 'beneficiary_address4';
    const BENEFICIARY_EMAIL     = 'beneficiary_email';
    const BENEFICIARY_MOBILE    = 'beneficiary_mobile';
    const BENEFICIARY_PIN       = 'beneficiary_pin';
    const BENEFICIARY_CITY      = 'beneficiary_city';
    const BENEFICIARY_STATE     = 'beneficiary_state';
    const BENEFICIARY_COUNTRY   = 'beneficiary_country';
    const DELETED_AT            = 'deleted_at';

    const IFSC_CODE_LENGTH      = 11;

    const SPECIAL_IFSC_CODE     = 'RZPB0000000';

    protected static $sign      = 'ba';

    protected $primaryKey = self::ID;

    protected $table = \RZP\Constants\Table::BANK_ACCOUNT;

    protected $entity = 'bank_account';

    protected $fillable = array(
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::TYPE,
        self::IFSC_CODE,
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
    );

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::TYPE,
        self::IFSC_CODE,
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
        self::BENEFICIARY_COUNTRY,
        self::BENEFICIARY_PIN,
        self::CREATED_AT
    );

    protected $public = array(
        self::ENTITY,
        self::IFSC_CODE,
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
        self::BENEFICIARY_COUNTRY,
        self::BENEFICIARY_PIN,
    );

    protected $guarded = array(self::ID);

    protected static $generators = array(
        self::ID,
        self::BENEFICIARY_COUNTRY,
    );

    protected $generateIdOnCreate = true;

    public function build(array $input = array())
    {
        (new Validator)->validateInput('addBankAccount', $input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

    protected function generateBeneficiaryCountry($input)
    {
        $this->setAttribute(self::BENEFICIARY_COUNTRY, 'IN');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function source()
    {
        $type = $this->getAttribute(self::TYPE);

        BankAccount\Type::validateType($type);

        $class = BankAccount\Type::getEntityClass($type);

        return $this->belongsTo($class, self::ENTITY_ID);
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

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    protected function setIfscCodeAttribute($code)
    {
        $code = strtoupper($code);

        $this->attributes[self::IFSC_CODE] = $code;
    }

    protected function getIfscCodeAttribute($code)
    {
        $code = $this->attributes[self::IFSC_CODE] = $code;

        return strtoupper($code);
    }

    public function generateIdFromCreatedAt()
    {
        $createdAt = $this->getAttribute(self::CREATED_AT);
        $this->setAttribute(
            self::ID,
            self::generateUniqueIdFromTimestamp($createdAt));
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
            $orig[self::BENEFICIARY_ADDRESS3],
            $orig[self::BENEFICIARY_ADDRESS4]);

        $copy = $baCopy->toArray();
        ksort($copy);
        unset(
            $copy[self::ID],
            $copy[self::CREATED_AT],
            $copy[self::UPDATED_AT],
            $copy[self::DELETED_AT],
            $copy[self::BENEFICIARY_ADDRESS3],
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
    public function getKotakBeneficaryCode()
    {
        $id = $this->getAttribute(self::ID);

        $first7 = substr($id, 0, 7);

        $last3 = substr($id, -3);

        $beneficiaryCode = $first7 . $last3;

        assert(strlen($beneficiaryCode) === 10);

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
}