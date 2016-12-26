<?php

namespace RZP\Services\Mock;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Services\Bitly as BaseBitly;

class Bitly extends BaseBitly
{
    public function __construct($app)
    {
        ;
    }

    public function shortenUrl($longUrl)
    {
        if (empty($longUrl) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BITLY_LONG_URL_EMPTY
            );
        }

        $randomUrl = 'http://bitly.dev/' . random_alphanum_string(7);

        return $randomUrl;
    }
}
