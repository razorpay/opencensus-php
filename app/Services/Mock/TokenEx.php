<?php

namespace RZP\Services\Mock;

use RZP\Services\TokenEx as BaseTokenEx;

class TokenEx extends BaseTokenEx
{
    public function tokenize($data)
    {
        $token = base64_encode($data);

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
        $data = base64_decode($token);

        return $data;
    }

    public function deleteToken($token)
    {
        return [
            'Error' => '',
            'ReferenceNumber' => rand(10000000000, 99999999999),
            'Success' => true,
        ];
    }
}