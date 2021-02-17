<?php


namespace RZP\Models\Merchant\AvgOrderValue;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

/**
 * Class Entity
 *
 * @property Merchant\Entity $merchant
 * @property Detail\Entity $merchantDetail
 *
 * @package RZP\Models\Merchant\AvgOrderValue
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const MIN_AOV               = 'min_aov';
    const MAX_AOV               = 'max_aov';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';
    protected $entity           = 'merchant_avg_order_value';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::MIN_AOV,
        self::MAX_AOV
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::MIN_AOV,
        self::MAX_AOV,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    public function merchantDetail()
    {
        return $this->belongsTo('RZP\Models\Merchant\Detail\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

    public function getMinAov()
    {
        return $this->getAttribute(self::MIN_AOV);
    }

    public function getMaxAov()
    {
        return $this->getAttribute(self::MAX_AOV);
    }

    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }
}
