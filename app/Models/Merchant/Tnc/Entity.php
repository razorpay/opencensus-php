<?php


namespace RZP\Models\Merchant\Tnc;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

/**
 * Class Entity
 *
 * @property Merchant\Entity $merchant
 * @property Detail\Entity $merchantDetail
 *
 * @package RZP\Models\Merchant\Tnc
 */
class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const DELIVERABLE_TYPE       = 'deliverable_type';
    const SHIPPING_PERIOD        = 'shipping_period';
    const REFUND_REQUEST_PERIOD  = 'refund_request_period';
    const REFUND_PROCESS_PERIOD  = 'refund_process_period';
    const WARRANTY_PERIOD        = 'warranty_period';

    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';

    protected $entity            = 'merchant_tnc';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::DELIVERABLE_TYPE,
        self::SHIPPING_PERIOD,
        self::REFUND_REQUEST_PERIOD,
        self::REFUND_PROCESS_PERIOD,
        self::WARRANTY_PERIOD
    ];

    protected $public = [
        self::ID,
        self::DELIVERABLE_TYPE,
        self::SHIPPING_PERIOD,
        self::REFUND_REQUEST_PERIOD,
        self::REFUND_PROCESS_PERIOD,
        self::WARRANTY_PERIOD
    ];

    public function merchantDetail()
    {
        return $this->belongsTo('RZP\Models\Merchant\Detail\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }
}
