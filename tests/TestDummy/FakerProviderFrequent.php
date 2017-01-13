<?php

namespace RZP\Tests\TestDummy;

use Faker\Provider\Base;
use Illuminate\Support\Str;

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

    public function timestamp($str = 'now')
    {
        return strtotime($str);
    }

    public function name($n = 5)
    {
        return Str::random($n);
    }

    public function rzpSubdomain()
    {
        return Str::random(5) . '.razorpay.com';
    }

    public function rzpEmail()
    {
        return Str::random(5) . '@razorpay.com';
    }
}
