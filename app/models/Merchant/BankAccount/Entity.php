<?php

namespace Models\Merchant\BankAccount;

use EE\Exception;
use Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID           = 'merchant_id';
    const BENEFICIARY_CODE      = 'beneficiary_code';
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

    const IFSC_CODE_LENGTH = 11;

    protected $primaryKey = self::MERCHANT_ID;

    protected $table = \Constants\Table::BANK_ACCOUNT;

    protected $entity = 'bank_account';

    protected $fillable = array(
        self::MERCHANT_ID,
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
        self::MERCHANT_ID,
        self::BENEFICIARY_CODE,
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

    protected $public = array(
        self::MERCHANT_ID,
        self::ENTITY,
        self::BENEFICIARY_CODE,
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
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected static $generators = array(
        self::BENEFICIARY_CODE,
        self::BENEFICIARY_COUNTRY,
    );

    public function build(array $input = array())
    {
        (new Validator)->validateInput('addBankAccount', $input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

    protected function generateBeneficiaryCode($input)
    {
        $name = $input[self::BENEFICIARY_NAME];

        // Caps all then remove spaces then cut first 4.
        $code = substr(str_replace(' ', '', strtoupper($name)), 0, 4);

        $this->setAttribute(self::BENEFICIARY_CODE, $code);
    }

    protected function generateBeneficiaryCountry($input)
    {
        $this->setAttribute(self::BENEFICIARY_COUNTRY, 'IN');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
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

    public function equals($baCopy)
    {
        $orig = $this->toArray();

        unset(
            $orig[self::CREATED_AT],
            $orig[self::UPDATED_AT],
            $orig[self::BENEFICIARY_CODE],
            $orig[self::BENEFICIARY_ADDRESS3],
            $orig[self::BENEFICIARY_ADDRESS4]);

        $copy = $baCopy->toArray();

        unset(
            $copy[self::CREATED_AT],
            $copy[self::UPDATED_AT],
            $copy[self::BENEFICIARY_ADDRESS3],
            $copy[self::BENEFICIARY_ADDRESS4],
            $copy[self::BENEFICIARY_CODE]);

        return ($orig == $copy);
    }
}
