<?php

namespace RZP\Models\Transaction\Statement\Ledger\Account;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Exception\LogicException;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement\Account
 *
 *
 */
class Entity extends Base\PublicEntity
{
    protected $entity = 'ledger_account';

    const MERCHANT_ID       = 'merchant_id';
    const STATUS            = 'status';
    const BALANCE           = 'balance';
    const MIN_BALANCE       = 'min_amount';

    // Relation names/attributes
    const ACCOUNT_DETAIL    = 'account_detail';

    protected $fillable = [
        self::BALANCE,
    ];

    protected $public = [
        self::ID,
        self::CREATED_AT,
    ];

    /**
     * Relations to be returned when receiving expand[] query param in fetch
     *
     * @var array
     */
    protected $expanded = [
        self::ACCOUNT_DETAIL,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $amounts = [
        self::BALANCE,
    ];

    protected $casts = [
        self::BALANCE             => 'int',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function AccountDetail()
    {
        return $this->belongsTo('RZP\Models\Transaction\Statement\Ledger\AccountDetail\Entity');
    }
}
