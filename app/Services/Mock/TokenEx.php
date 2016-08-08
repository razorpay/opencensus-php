<?php

namespace RZP\Services\Mock;

use Str;
use Cache;
use RZP\Services\TokenEx as BaseTokenEx;

class TokenEx extends BaseTokenEx
{
    public function tokenize($data)
    {
        $token = Str::quickRandom(8);

        Cache::store('file')->forever($token, $data);

        return $token;
    }

    public function validateToken($token)
    {
        return [
            'Error' => '',
            'Valid' => true,
            'ReferenceNumber' => rand(10000000000, 99999999999),
            'Success' => true,
        ];
    }

    public function detokenize($token)
    {
        $data = Cache::store('file')->get($token);

        return $data;
    }

    public function deleteToken($token)
    {
        Cache::store('file')->forget($token);

        return [
            'Error' => '',
            'ReferenceNumber' => rand(10000000000, 99999999999),
            'Success' => true,
        ];
    }
}