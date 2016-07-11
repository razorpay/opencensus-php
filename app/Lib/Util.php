<?php

namespace Lib;

class Util
{
    public static function random_alpha_string($length = 1)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($chars), 0, $length);
    }
}
