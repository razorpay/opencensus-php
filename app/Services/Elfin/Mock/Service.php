<?php

namespace RZP\Services\Elfin\Mock;

use Illuminate\Config\Repository as Config;
use RZP\Services\Elfin;

class Service extends Elfin\Service
{
    /**
     * Shorten given url.
     *
     * @param string       $url
     * @param bool|boolean $fail - If fail is passed as true it'll bubble up ex.
     *
     * @return string
     */
    public function shorten(string $url, bool $fail = false)
    {
        //
        // Generates random short url and returns
        //

        $url = 'http://dwarf.razorpay.dev/' . random_alphanum_string(7);

        return $url;
    }
}
