<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\Vpa\Handle;

/**
 * @property Handle\Entity $parentHandle
 *
 * Trait HasHandle
 * @package RZP\Models\P2p\Base\Traits
 */
trait HasHandle
{
    public function hasHandle(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    /**
     * @return $this
     */
    public function setHandle(string $handle)
    {
        return $this->setAttribute(self::HANDLE, $handle);
    }

    public function associateHandle(Handle\Entity $handle)
    {
        return $this->parentHandle()->associate($handle);
    }

    public function scopeHandle(BuilderEx $query, Handle\Entity $handle)
    {
        return $query->where(self::HANDLE, $handle->getCode());
    }

    public function parentHandle()
    {
        return $this->belongsTo(Handle\Entity::class, self::HANDLE);
    }

    // Gateway Implementation for Handle

    /**
     * @return array
     */
    public function getGatewayData()
    {
        return $this->getAttribute(self::GATEWAY_DATA);
    }

    /**
     * @return $this
     */
    public function setGatewayData(array $gatewayData)
    {
        return $this->setAttribute(self::GATEWAY_DATA, $gatewayData);
    }

    /**
     * @return $this
     */
    public function mergeGatewayData(array $gatewayData)
    {
        return $this->setGatewayData(array_merge($this->getGatewayData(), $gatewayData));
    }
}
