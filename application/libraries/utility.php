<?php

class Utility
{
    private function __construct() {}

    public static function generate_token($len)
    {
        return bin2hex(openssl_random_pseudo_bytes($len*2));
    }
}