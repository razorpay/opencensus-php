<?php

namespace RZP\Services\Mock;

use RZP\Services\CardVault as BaseCardVault;

class CardVault extends BaseCardVault
{
    public function tokenize($data)
    {
        $token = base64_encode($data);

        return $token;
    }

    public function validateToken($token)
    {
        return [
            'error' => '',
            'success' => true,
        ];
    }

    public function detokenize($token)
    {
        $data = base64_decode($token);

        return $data;
    }

    public function getTokenexToken($token)
    {
        $data = base64_decode($token);

        return $data;
    }

    public function deleteToken($token)
    {
        return [];
    }

    public function getVaultTokenFromTokenexToken($token)
    {
        $card = $this->detokenize($token);

        return $this->tokenize($card);
    }
}
