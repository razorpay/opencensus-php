<?php


namespace RZP\Models\Mpan;

use RZP\Models\Base;
use RZP\Constants;
class Entity extends Base\PublicEntity
{
    const MPAN          = 'mpan';
    const NETWORK       = 'network';
    const ASSIGNED      = 'assigned';
    const MERCHANT_ID   = 'merchant_id';

    const ID_LENGTH = 16;

    protected $public = [
        self::MPAN,
        self::NETWORK,
        self::ASSIGNED,
        self::MERCHANT_ID,
    ];

    protected $fillable = [
        self::MPAN,
        self::NETWORK,
        self::ASSIGNED,
    ];

    protected $visible = [
        self::MPAN,
        self::NETWORK,
    ];

    protected $publicSetters = [
        self::MPAN,
        self::NETWORK,
        self::ASSIGNED,
        self::MERCHANT_ID,
    ];

    protected static $sign = self::MPAN;

    protected $primaryKey = self::MPAN;

    protected $entity = Constants\Entity::MPAN;

    protected $defaults = [
        self::ASSIGNED          => false,
        self::MERCHANT_ID       => null,
    ];

    protected $casts = [
        self::MPAN              => 'string',
        self::ASSIGNED          => 'bool',
        self::NETWORK           => 'string',
        self::MERCHANT_ID       => 'string',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function getMpan()
    {
        return $this->getAttribute(self::MPAN);
    }

    public function getMaskedMpan(string $mpan)
    {
        if (empty($mpan) === true)
        {
            return $mpan;
        }

        $maskedMpan =  substr($mpan,0, 6) . str_repeat("*", 6) . substr($mpan, -4);

        return $maskedMpan;
    }

    public function setPublicMpanAttribute(array &$array)
    {

    }

    public function setPublicNetworkAttribute(array &$array)
    {

    }

    public function setPublicAssignedAttribute(array &$array)
    {

    }

    public function setPublicMerchantIdAttribute(array &$array)
    {

    }
}
