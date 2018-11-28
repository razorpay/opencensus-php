<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\Vpa\Handle;

trait HasHandle
{
    protected static function bootHasHandle()
    {
        self::$doesEntityHasHandle = true;
    }

    public function handleRelation()
    {
        return $this->belongsTo(Handle\Entity::class, 'handle', 'handle');
    }

    public function scopeHandle(BuilderEx $query, Handle\Entity $handle)
    {
        $query->where(self::HANDLE, $handle->getHandle());
    }
}
