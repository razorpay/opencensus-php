<?php

namespace RZP\Models\Base\Traits;

trait ExternalOwner
{
    protected $external = false;

    public function setExternal(bool $external)
    {
        $this->external = $external;
    }

    public function isExternal(): bool
    {
        return $this->external;
    }
}
