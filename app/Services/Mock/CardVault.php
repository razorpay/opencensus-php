<?php

namespace RZP\Services\Mock;

use RZP\Services\CardVault as BaseCardVault;
use RZP\Models\Card\Vault;

class CardVault extends BaseCardVault
{
    public function ping()
    {
        return true;
    }

    public function tokenize($input)
    {
        if (isset($input['card']) === true)
        {
            $token = base64_encode($input['card']);
        }
        else
        {
            $token = base64_encode($input['secret']);
        }

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

    public function renewVaultToken(): array
    {
        return [
            'success'    => true,
            'error'      => '',
            'expiry_time'=> date('Y-m-d H:i:s', strtotime('+1 year'))
        ];
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

    public function createTokenizedCard($input)
    {
        $response['success'] = true;
        $response['provider'] = $input['provider']['network'];
        $token = base64_encode($input['card']['number']);

        $response['success'] = true;
        $response['token']  = $token;
        $response['fingerprint'] = strrev($token);
        $response['token_iin'] = substr($input['card']['number'] ?? null, 0, 6);
        $response['last4'] = substr($input['card']['number'] ?? null, 0, 4);
        $response['expiry_month'] = $input['card']['expiry_month'];
        $response['expiry_year'] = $input['card']['expiry_year'];
        $response['length'] = strlen($input['card']['number']);

        if (strlen($response['expiry_year']) > 2)
        {
            $response['expiry_year'] = '20' . $response['expiry_year'];
        }

        $response['service_providers'] = [
            [
                'type'  => 'network',
                'name'  => $input['provider']['network'],
                'data'  => [
                    'token_reference_number' => $token,
                    'card_reference_number'  => strrev($token),
                    'interoperable'          => true,
                ],
            ]
        ];

        return $response;
    }

    public function getTokenAndFingerprint($input)
    {
        $token = base64_encode($input['card']);
        $response['token'] = $token;
        $response['fingerprint'] = strrev($token);
        $response['scheme'] = Vault::RZP_VAULT_SCHEME;

        return $response;
    }

    public function encrypt($input)
    {
        $token = base64_encode($input['card']);

        return $token;
    }

    public function decrypt($token)
    {
        $data = base64_decode($token);

        return $data;
    }
}
