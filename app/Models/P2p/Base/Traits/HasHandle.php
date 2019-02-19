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
}
