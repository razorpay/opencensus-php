<?php

namespace RZP\Models\Merchant\Product\TncMap\Acceptance;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const TNC_MAP_ID                = 'tnc_map_id';
    const MERCHANT_ID               = 'merchant_id';
    const ACCEPTED_CHANNEL          = 'accepted_channel';
    const CLIENT_IP                 = 'client_ip';
    const CLIENT_DEVICE             = 'client_device';

    protected $entity               = 'merchant_tnc_acceptance';

    protected $generateIdOnCreate   = true;

    protected  $primaryKey          = self::ID;

    protected static $sign          = 'tnc';

    protected $fillable = [
        self::MERCHANT_ID,
        self::ACCEPTED_CHANNEL,
        self::CLIENT_DEVICE,
        self::CLIENT_IP,
    ];

    protected $public = [
        self::ID,
        self::TNC_MAP_ID,
        self::MERCHANT_ID,
        self::ACCEPTED_CHANNEL,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', self::MERCHANT_ID, self::ID);
    }

    public function tncMap()
    {
        return $this->belongsTo('RZP\Models\Merchant\Product\TncMap\Entity', self::TNC_MAP_ID, self::ID);
    }

    public function getAcceptedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getClientIp()
    {
        return $this->getAttribute(self::CLIENT_IP);
    }

    public function getAcceptedChannel()
    {
        return $this->getAttribute(self::ACCEPTED_CHANNEL);
    }
}
