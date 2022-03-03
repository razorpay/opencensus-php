<?php

namespace RZP\Models\PayoutOutbox;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID            = 'id';
    const PAYOUT_DATA   = 'payout_data';
    const MERCHANT_ID   = 'merchant_id';
    const USER_ID       = 'user_id';
    const REQUEST_TYPE  = 'request_type';
    const SOURCE        = 'source';
    const PRODUCT       = 'product';
    const DELETED_AT    = 'deleted_at';
    const CREATED_AT    = 'created_at';
    const EXPIRES_AT    = 'expires_at';
    const UPDATED_AT    = null; // assigning it null, since laravel looks for updated_at value in every create/update call. Since we do not update this entry, this column is not needed
    const STATUS        = 'status';

    protected $entity = 'payout_outbox';

    protected $fillable = [
        self::PAYOUT_DATA,
        self::MERCHANT_ID,
        self::USER_ID,
        self::REQUEST_TYPE,
        self::SOURCE,
        self::PRODUCT,
        self::DELETED_AT,
        self::EXPIRES_AT,
    ];

    protected $public = [
        self::ID,
        self::STATUS,
    ];

    protected $primaryKey = self::ID;

    protected $dates = [
        self::CREATED_AT,
        self::EXPIRES_AT,
        self::DELETED_AT,
    ];

    protected $defaults = [
        self::PRODUCT => 'banking',
    ];

    protected $generateIdOnCreate = false;

    public function getMerchantId() {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getUserId() {
        return $this->getAttribute(self::USER_ID);
    }

    public function getPayoutData() {
        return $this->getAttribute(self::PAYOUT_DATA);
    }

    public function getRequestType() {
        return $this->getAttribute(self::REQUEST_TYPE);
    }

    public function getSource() {
        return $this->getAttribute(self::SOURCE);
    }

    public function getProduct() {
        return $this->getAttribute(self::PRODUCT);
    }

    public function getExpiryTime() {
        return $this->getAttribute(self::EXPIRES_AT);
    }
}
