<?php

namespace RZP\Models\P2p\Base\Traits;

use Illuminate\Database\Eloquent;

trait SoftDeletes
{
    use Eloquent\SoftDeletes;

    public function canSoftDelete(): bool
    {
        return true;
    }
}
