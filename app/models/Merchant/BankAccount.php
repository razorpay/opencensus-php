<?php

namespace Models\Merchant;

use EE\Exception;
use Models\Base;

class BankAccount extends Base\UniqueIdEntity
{
    const MERCHANT_ID = 'merchant_id';
    const IFSC_CODE = 'ifsc_code';
    const BENEFICIARY_NAME = 'beneficiary_name';
    const ACCOUNT_NUMBER = 'account_number';

    const IFSC_CODE_LENGTH = 11;

    protected $primaryKey = self::MERCHANT_ID;

    protected $table = \Constants\Table::BANK_ACCOUNT;

    protected $fillable = array(
        self::MERCHANT_ID,
        self::IFSC_CODE,
        self::BENEFICIARY_NAME,
        self::ACCOUNT_NUMBER);

    protected $visible = array(
        self::MERCHANT_ID,
        self::IFSC_CODE,
        self::BENEFICIARY_NAME,
        self::ACCOUNT_NUMBER);

    public function build(array $input = array())
    {
        (new Validator)->validateInput('addBankAccount', $input);

        $this->fill($input);

        return $this;
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
}
