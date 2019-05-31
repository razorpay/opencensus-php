<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const ACCOUNT_NUMBER        = 'account_number';
    const ACCOUNT_IFSC_CODE     = 'account_ifsc_code';
    const PINCODE               = 'pincode';
    const STATUS                = 'status';
    const BANK_INTERNAL_STATUS  = 'bank_internal_status';
    const BANK                  = 'bank';
    const FTS_FUND_ACCOUNT_ID   = 'fts_fund_account_id';
    const BALANCE_ID            = 'balance_id';
    const BANK_REFERENCE_NUMBER = 'bank_reference_number';

    const PINCODE_LENGTH    = '6';

    // need to confirm this length
    const ACCOUNT_NUMBER_LENGTH = '40';
    const ACCOUNT_IFSC_CODE_LENGTH  = '11';

    const MERCHANT_DATA = 'merchant_data';

    protected $entity = 'banking_account';

    protected $validStatus = [
        Status::CREATED,
        Status::INITIATED,
    ];

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC_CODE,
        self::STATUS,
        self::PINCODE,
        self::FTS_FUND_ACCOUNT_ID,
        self::BANK,
        self::BALANCE_ID,
        self::BANK_REFERENCE_NUMBER,
        self::BANK_INTERNAL_STATUS,
    ];

    protected $visible = [
        self::ID,
        self::BANK_REFERENCE_NUMBER,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC_CODE,
        self::STATUS,
        self::PINCODE,
        self::BANK,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::BANK,
        self::BANK_REFERENCE_NUMBER,
        self::STATUS,
    ];

    protected static $generators = [
        self::ID,
        self::BANK_REFERENCE_NUMBER,
    ];

    public function generateBankReferenceNumber()
    {
        $id = substr(time(), 0, 5);

        $this->setAttribute(self::BANK_REFERENCE_NUMBER, $id);
    }

    public function generateId()
    {
        $this->setAttribute(self::ID, static::generateUniqueId());
    }

    // -------------------------- Setters ------------------------------------- //
    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    // ------------------------ associations --------------------------------- //

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function balance()
    {
        return $this->belongsTo(Balance\Entity::class);
    }
}
