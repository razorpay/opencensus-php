<?php

namespace RZP\Services\Mock;

use RZP\Services\MaxMind as BaseMaxmind;

class MaxMind extends BaseMaxmind
{
    const LICENSE_KEY = 'license_key';

    public function __construct($app)
    {
        ;
    }

    public function query($input)
    {
        return null;
    }
}