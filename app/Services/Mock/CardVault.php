<?php

namespace RZP\Services\Mock;

use RZP\Services\CardVault as BaseCardVault;
use RZP\Models\Card\Vault;

class CardVault extends BaseCardVault
{
    public function tokenize($input)
    {
        $token = base64_encode($input['card']);

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

    public function deleteToken($token)
    {
        return [];
    }

    public function createVaultToken(array $input): array
    {
        return [
            'success'       => true,
            'error'         => '',
            'token'         => base64_encode($input['secret']),
            'fingerprint'   => base64_encode($input['secret']),
            'version'       => 'v1',
        ];
    }

    public function getVaultTokenFromTempToken($token)
    {
        $response['token'] = strrev($token);
        $response['fingerprint'] = strrev($token);

        return $response;
    }

    public function getTokenAndFingerprint($input)
    {
        $token = base64_encode($input['card']);
        $response['token'] = $token;
        $response['fingerprint'] = strrev($token);
        $response['scheme'] = Vault::RZP_VAULT_SCHEME;
    }
}
