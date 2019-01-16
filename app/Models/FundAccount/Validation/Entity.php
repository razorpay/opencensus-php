<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\FundAccount\Entity as FundAccount;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                    = 'id';
    const RECEIPT               = 'receipt';
    const MERCHANT_ID           = 'merchant_id';
    const FUND_ACCOUNT_ID       = 'fund_account_id';
    // Fund Account Type is added just for faster filtering
    const FUND_ACCOUNT_TYPE     = 'fund_account_type';
    const STATUS                = 'status';
    const FEE                   = 'fee';
    const TAX                   = 'tax';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const ERROR_CODE            = 'error_code';
    const INTERNAL_ERROR_CODE   = 'internal_error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const NOTES                 = 'notes';

    // Key for the response
    const FUND_ACCOUNT          = 'fund_account';

    protected $entity           = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::RECEIPT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::FUND_ACCOUNT_ID,
        self::STATUS,
        self::FEE,
        self::TAX,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::ERROR_CODE,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self:: CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::FUND_ACCOUNT,
        self::STATUS,
        self::FEE,
        self::TAX,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self:: CREATED_AT,
    ];

    protected $defaults = [
        self::STATUS               => Status::CREATED,
        self::NOTES                => [],
        self::AMOUNT               => null,
        self::FEE                  => null,
        self::TAX                  => null,
        self::CURRENCY             => null,
    ];


    protected $casts = [
        self::AMOUNT               => 'int',
        self::FEE                  => 'int',
        self::TAX                  => 'int',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::FEE,
        self::TAX,
    ];

    // -------------- Relations --------------

    public function fundAccount()
    {
        return $this->belongsTo(FundAccount::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    // ------------ End Relations ------------
}
