<?php

function getStringToHash($content, $glue = '')
{
    unset($content['vpc_SecureHashType']);

    $input = [];

    foreach ($content as $k => $v)
    {
        $input[] = $k . '=' . $v;
    }

    return implode('&', $input);
}

function getHashOfString($str, $secret)
{
    $secret = pack("H*", $secret);

    return strtoupper(hash_hmac('sha256', $str, $secret));
}