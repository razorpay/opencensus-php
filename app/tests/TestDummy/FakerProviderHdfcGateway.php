<?php

namespace Tests\TestDummy;

use Carbon\Carbon;
use Faker\Provider\Base;

class FakerProviderHdfcGateway extends Base
{
    public function hdfcPostDate()
    {
        return (new Carbon('now', 'Asia/Kolkata'))->format('md');
    }

    public function hdfcRef()
    {
        return random_integer(12);
    }

    public function hdfcPaymentId()
    {
        return random_integer(15);
    }
}