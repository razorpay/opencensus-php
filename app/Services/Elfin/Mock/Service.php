<?php

namespace RZP\Services\Elfin\Mock;

use Illuminate\Config\Repository as Config;
use RZP\Services\Elfin;

class Service extends Elfin\Service
{

    // we use this to expand a given short url to it's original form
    static array $shortURLToURL = [];

    /**
     * {@inheritDoc}
     */
    public function shorten(string $url, array $input = [], bool $fail = false)
    {
        //
        // Generates random short url and returns
        //

        $shortURL = 'http://dwarf.razorpay.in/' . random_alphanum_string(7);

        self::$shortURLToURL[$shortURL] = $url;

        return $shortURL;
    }
}
