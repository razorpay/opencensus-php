<?php

namespace RZP\Models\Settlement\OndemandFundAccount;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Settings;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $generateIdOnCreate = true;

    protected static $sign = 'sodfa';

    const ID_LENGTH = 14;

    protected $entity = 'settlement.ondemand_fund_account';

    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const CONTACT_ID             = 'contact_id';
    const FUND_ACCOUNT_ID        = 'fund_account_id';
    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';
    const DELETED_AT             = 'deleted_at';

    protected $public = [
        self::ID,
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
    ];

    protected $fillable = [
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
    ];

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function getContactId(): string
    {
        return $this->getAttribute(self::CONTACT_ID);
    }

    public function getFundAccountId(): string
    {
        return $this->getAttribute(self::FUND_ACCOUNT_ID);
    }

    public function setFundAccountIdNull()
    {
        $this->setAttribute(self::FUND_ACCOUNT_ID, null);
    }
}
