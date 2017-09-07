<?php

namespace RZP\Tests\TestDummy;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Faker\Provider\Base;

class FakerProviderHdfcGateway extends Base
{
    public function hdfcPostDate()
    {
        return (new Carbon('now', Timezone::IST))->format('md');
    }

    public function hdfcRef()
    {
        return random_integer(12);
    }

    public function hdfcPaymentId()
    {
        return random_integer(5);
    }
}
