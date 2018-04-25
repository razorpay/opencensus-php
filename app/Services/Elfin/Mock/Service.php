<?php

namespace RZP\Services\Elfin\Mock;

use Illuminate\Config\Repository as Config;
use RZP\Services\Elfin;

class Service extends Elfin\Service
{
    /**
     * {@inheritDoc}
     */
    public function shorten(string $url, array $input = [], bool $fail = false)
    {
        //
        // Generates random short url and returns
        //

        $url = 'http://dwarf.razorpay.in/' . random_alphanum_string(7);

        return $url;
    }
}
