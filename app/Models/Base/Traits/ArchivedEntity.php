<?php

namespace RZP\Models\Base\Traits;

trait ArchivedEntity
{
    protected $archived = false;

    public function setArchived(bool $archived)
    {
        $this->archived = $archived;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }
}
