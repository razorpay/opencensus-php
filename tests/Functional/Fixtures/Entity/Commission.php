<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Illuminate\Support\Facades\Artisan;

class Commission extends Base
{
    public function createCommissionAndSyncEs(array $attributes = [])
    {
        parent::create($attributes);

        Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'commission']);
    }
}
