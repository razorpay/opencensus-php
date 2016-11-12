<?php

namespace RZP\Services\Mock;

use RZP\Services\Bitly as BaseBitly;

class Bitly extends BaseBitly
{
    public function __construct($app)
    {
        ;
    }

    public function shortenUrl($longUrl)
    {
        $randomUrl = 'http://bit.ly/' . random_alphanum_string(7);

        return $randomUrl;
    }
}
