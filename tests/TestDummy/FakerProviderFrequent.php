<?php

namespace RZP\Tests\TestDummy;

use Faker\Provider\Base;

class FakerProviderFrequent extends Base
{
    public function uniqueid()
    {
        return \RZP\Models\Base\UniqueIdEntity::generateUniqueId();
    }

    public function emptyarray()
    {
        return array();
    }

    public function timestamp()
    {
        return time();
    }
}