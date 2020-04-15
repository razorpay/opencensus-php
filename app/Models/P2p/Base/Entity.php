<?php

namespace RZP\Models\P2p\Base;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Entity extends Base\PublicEntity
{
    // Common constants across Entities,
    const REQUEST       = 'request';
    const RESPONSE      = 'response';
    const SUCCESS       = 'success';
    const DEVICE_ID     = 'device_id';
    const REFRESHED_AT  = 'refreshed_at';
    const HANDLE        = 'handle';
    const GATEWAY       = 'gateway';
    const GATEWAY_DATA  = 'gateway_data';
    const CALLBACK      = 'callback';
    const SDK           = 'sdk';
    const SMS           = 'sms';
    const POLL          = 'poll';
    const UPI           = 'upi';
    const ACTION        = 'action';
    const DATA          = 'data';
    const CONTEXT       = 'context';

    /********** Input Keys **********/
    const DEVICE        = 'device';
    const VPAS          = 'vpas';

    /**
     * Generator for refreshed at
     * @return $this
     */
    public function generateRefreshedAt()
    {
        return $this->setAttribute(static::REFRESHED_AT, Carbon::now()->getTimestamp());
    }

    /**
     * @return integer|null
     */
    public function getRefreshedAt()
    {
        return $this->getAttribute(static::REFRESHED_AT);
    }

    public function hasMerchant(): bool
    {
        return false;
    }

    public function hasHandle(): bool
    {
        return false;
    }

    public function hasDevice(): bool
    {
        return false;
    }

    public function hasCustomer(): bool
    {
        return false;
    }

    public function canSoftDelete(): bool
    {
        return false;
    }

    public function toArrayBag()
    {
        return (new ArrayBag($this->attributesToArray()));
    }

    public function getP2pEntityName()
    {
        $entity = $this->entity;

        if (starts_with($this->entity, 'p2p_'))
        {
            $entity = substr($this->entity, 4);
        }

        return $entity;
    }

    public function setPublicEntityAttribute(array & $array)
    {
        $array[self::ENTITY] = $this->getP2pEntityName();
    }
}
