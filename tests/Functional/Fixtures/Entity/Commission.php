<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Illuminate\Support\Facades\Artisan;

class Commission extends Base
{
    public function createCommissionAndSyncEs(array $attributes = [])
    {
        $commission = parent::create($attributes);

        Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'commission']);

        return $commission;
    }
}
