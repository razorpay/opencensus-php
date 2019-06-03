<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const CHANNEL               = 'channel';
    const ACCOUNT_NUMBER        = 'account_number';
    const ACCOUNT_IFSC_CODE     = 'account_ifsc_code';
    const PINCODE               = 'pincode';
    const STATUS                = 'status';
    const BANK_INTERNAL_STATUS  = 'bank_internal_status';
    const FTS_FUND_ACCOUNT_ID   = 'fts_fund_account_id';
    const BALANCE_ID            = 'balance_id';
    const BANK_REFERENCE_NUMBER = 'bank_reference_number';

    const PINCODE_LENGTH    = '6';

    // TODO: need to confirm this length
    const ACCOUNT_NUMBER_LENGTH     = '40';
    const ACCOUNT_IFSC_CODE_LENGTH  = '11';

    const MERCHANT_DATA = 'merchant_data';

    protected $entity = 'banking_account';

    protected static $sign = 'bankacc';

    protected $generateIdOnCreate = true;

    protected $validStatus = [
        Status::CREATED,
        Status::INITIATED,
    ];

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::CHANNEL,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC_CODE,
        self::STATUS,
        self::PINCODE,
        self::FTS_FUND_ACCOUNT_ID,
        self::BALANCE_ID,
        self::BANK_REFERENCE_NUMBER,
        self::BANK_INTERNAL_STATUS,
    ];

    protected $visible = [
        self::ID,
        self::CHANNEL,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC_CODE,
        self::PINCODE,
        self::BANK_REFERENCE_NUMBER,
        self::STATUS,
        self::BANK_INTERNAL_STATUS,
        self::BALANCE_ID,
        self::FTS_FUND_ACCOUNT_ID,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::CHANNEL,
        self::BANK_REFERENCE_NUMBER,
        self::STATUS,
    ];

    protected static $generators = [
        self::BANK_REFERENCE_NUMBER,
    ];

    // -------------------------- Generators --------------------------------- //

    public function generateBankReferenceNumber()
    {
        // TODO: fix.
        $id = substr(time(), 0, 5);

        $this->setAttribute(self::BANK_REFERENCE_NUMBER, $id);
    }

    // ---------------------------- Setters ----------------------------------- //

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    // --------------------------- Relations ---------------------------------- //

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function balance()
    {
        return $this->belongsTo(Balance\Entity::class);
    }
}
