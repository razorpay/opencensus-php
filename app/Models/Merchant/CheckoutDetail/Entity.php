<?php


namespace RZP\Models\Merchant\CheckoutDetail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

/**
 * Class Entity
 *
 * @property Merchant\Entity $merchant
 */
class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const STATUS_1CC            = 'status_1cc';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';

    protected $entity           = 'merchant_checkout_detail';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::STATUS_1CC
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::STATUS_1CC,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function setStatus1cc(string $status)
    {
        $this->setAttribute(self::STATUS_1CC, $status);
    }

    public function getStatus1cc()
    {
        return $this->getAttribute(self::STATUS_1CC);
    }
}
