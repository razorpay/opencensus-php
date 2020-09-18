<?php

namespace RZP\Models\Base\Traits;

trait Archive
{
    public function archive()
    {
        $archivalEntity = clone $this;
        $archivalEntity->archivalEntity = true;
        $archivalEntity->exists = false;
        $archivalEntity->timestamps = false;
        $archivalEntity->saveOrFail();

        $this->deleteOrFail();
    }

    public function getTable()
    {
        if ($this->archivalEntity === true)
        {
            return parent::getTable() . '_archive';
        }

        return parent::getTable();
    }
}
