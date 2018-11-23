<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\Vpa\Handle;

trait HasHandle
{
    public function hasHandle(): bool
    {
        return true;
    }

    public function associateHandle(Handle\Entity $handle)
    {
        return $this->handleRelation()->associate($handle);
    }

    public function scopeHandle(BuilderEx $query, Handle\Entity $handle)
    {
        return $query->where(self::HANDLE, $handle->getHandle());
    }

    public function handleRelation()
    {
        return $this->belongsTo(Handle\Entity::class, self::HANDLE, self::HANDLE);
    }
}
