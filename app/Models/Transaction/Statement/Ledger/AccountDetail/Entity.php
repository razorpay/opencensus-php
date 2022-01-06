<?php

namespace RZP\Models\Transaction\Statement\Ledger\AccountDetail;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Exception\LogicException;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement\AccountDetail
 *
 *
 */
class Entity extends Base\PublicEntity
{
    protected $entity = 'account_detail';

    const MERCHANT_ID       = 'merchant_id';
    const ACCOUNT_ID        = 'account_id';
    const ACCOUNT_NAME      = 'account_name';
    const CURRENCY          = 'currency';
    const TENANT            = 'tenant';
    const PARENT_ACCOUNT_ID = 'parent_account_id';
    const ACCOUNT_CATEGORY  = 'account_category';
    const BUSINESS_CATEGORY = 'businesss_category';
    const ENTITIES          = 'entities';

    protected $fillable = [
        self::CURRENCY,
        self::TENANT,
        self::ACCOUNT_ID,
        self::ACCOUNT_NAME,
    ];

    protected $public = [
        self::ID,
        self::TENANT,
        self::ACCOUNT_ID,
        self::CURRENCY,
        self::CREATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function getAccountId()
    {
        return $this->getAttribute(self::ACCOUNT_ID);
    }
}
