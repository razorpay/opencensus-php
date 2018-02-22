<?php

namespace RZP\Tests\TestDummy;

use Carbon\Carbon;
use Faker\Provider\Base;
use Illuminate\Support\Str;
use RZP\Constants\Timezone;

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

    public function timestamp($modifyDays = 0)
    {
        $time = Carbon::now(Timezone::IST);

        $time = ($modifyDays > 0) ? $time->addDays($modifyDays) :
                                    $time->subDays($modifyDays);

        return $time->timestamp;
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
