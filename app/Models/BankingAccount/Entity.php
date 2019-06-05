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
    const ACCOUNT_IFSC          = 'account_ifsc';
    const PINCODE               = 'pincode';
    const STATUS                = 'status';
    const BANK_INTERNAL_STATUS  = 'bank_internal_status';
    const FTS_FUND_ACCOUNT_ID   = 'fts_fund_account_id';
    const BALANCE_ID            = 'balance_id';
    const BANK_REFERENCE_NUMBER = 'bank_reference_number';

    const PINCODE_LENGTH    = '6';

    // TODO: need to confirm this length
    const ACCOUNT_NUMBER_LENGTH     = '40';
    const ACCOUNT_IFSC_LENGTH  = '11';

    protected $entity = 'banking_account';

    protected static $sign = 'bankacc';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::CHANNEL,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC,
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
        self::ACCOUNT_IFSC,
        self::PINCODE,
        self::BANK_REFERENCE_NUMBER,
        self::STATUS,
        self::BANK_INTERNAL_STATUS,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::CHANNEL,
        self::BANK_REFERENCE_NUMBER,
        self::STATUS,
        self::BANK_INTERNAL_STATUS,
    ];

    // ---------------------------- Setters ----------------------------------- //

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setBankReferenceNumber(string $number)
    {
        $this->setAttribute(self::BANK_REFERENCE_NUMBER, $number);
    }

    // -------------------------- Getters ------------------------------------ //

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
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
