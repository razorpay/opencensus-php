<?php

namespace RZP\Models\Base\Traits;

trait HardDeletes
{
    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->setKeysForSaveQuery($this->newQueryWithoutScopes())->delete();

        $this->exists = false;
    }
}
