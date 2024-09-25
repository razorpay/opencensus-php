<?php

namespace RZP\Models\Merchant\Acs\Traits;
use RZP\Trace\TraceCode;

trait AsvUpdateTimeStamp
{
    public function updateTimestamps()
    {
        $time = $this->freshTimestamp();
        $updatedAtColumn = $this->getUpdatedAtColumn();

        if (! is_null($updatedAtColumn))
        {
            if($this->isDirty($updatedAtColumn))
            {
                app('trace')->info(TraceCode::UPDATED_AT_DIRTY);
            }

            $this->setUpdatedAt($time);
        }

        $createdAtColumn = $this->getCreatedAtColumn();
        if (! $this->exists && ! is_null($createdAtColumn) && ! $this->isDirty($createdAtColumn))
        {
            $this->setCreatedAt($time);
        }

        return $this;
    }

}
