<?php

namespace RZP\Gateway\Netbanking\Vijaya;

use DOMDocument;

class VerifyResponse
{
    const SUCCESS = 'Your Payment is Successful';
    const FAILURE = 'Payment Record Not Found Check the Parameters sent';

    const STATUS_LIST = [
        self::SUCCESS,
        self::FAILURE
    ];

    public static function isSuccess($body): bool
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $dom->loadHTML($body);

        $h4tags = [];

        //TODO : should the entire html be searched or only h4 tags
        foreach ($dom->getElementsByTagName('h4') as $node)
        {
            $h4tags[] = trim(strip_tags($dom->saveHTML($node)));
        };

        if (in_array(self::SUCCESS, $h4tags))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
