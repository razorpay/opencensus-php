<?php

namespace Tests\TestDummy;

use Faker\Provider\Base;

class FakerProviderFrequent extends Base
{
    public function uniqueid()
    {
        return \Models\Base\UniqueIdEntity::generateUniqueId();
    }

    public function emptyarray()
    {
        return array();
    }
}