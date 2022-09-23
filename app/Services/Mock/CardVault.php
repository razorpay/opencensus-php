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

    public function tokenize($input, $buNamespace=null)
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

    public function detokenize($token,$buNamespace=null)
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

    public function getVaultTokenFromTempToken($token , $buNamespace =null)
    {
        $response['token'] = strrev($token);
        $response['fingerprint'] = strrev($token);

        return $response;
    }

    public function createTokenizedCard($input): array
    {
        $response['success'] = true;
        $token = base64_encode($input['card']['number']);

        $response['success'] = true;
        $response['token']  = $token;
        $response['fingerprint'] = strrev($token);
        $response['token_iin'] = substr($input['card']['number'] ?? null, 0, 6);
        $response['expiry_month'] = $input['card']['expiry_month'];
        $response['expiry_year'] = $input['card']['expiry_year'];

        if (strlen($response['expiry_year']) > 2)
        {
            $response['expiry_year'] = '20' . $response['expiry_year'];
        }

        $response['service_provider_tokens'] = [
            [
                'id'             => 'spt_1234abcd',
                'entity'         => 'service_provider_token',
                'provider_type'  => 'network',
                'provider_name'  => $input['provider']['network'],
                'interoperable'  => true,
                'status'         => 'activated',
                'provider_data'  => [
                    'token_reference_number'     => $token,
                    'payment_account_reference'  => strrev($token),
                    'token_iin'                  => '453335',
                    'token_expiry_month'         => 12,
                    'token_expiry_year'          => 2021,
                ],
            ]
        ];

        return $response;
    }

    public function migrateToTokenizedCard($input): array
    {
        $response['success'] = true;
        $token = base64_encode($input['card']['vault_token']);

        $response['success'] = true;
        $response['token']  = $token;
        $response['fingerprint'] = strrev($token);
        $response['token_iin'] = '411111';
        $response['expiry_month'] = $input['card']['expiry_month'];
        $response['expiry_year'] = $input['card']['expiry_year'];

        if (strlen($response['expiry_year']) > 2)
        {
            $response['expiry_year'] = '20' . $response['expiry_year'];
        }

        $response['service_provider_tokens'] = [
            [
                'id'             => 'spt_1234abcd',
                'entity'         => 'service_provider_token',
                'provider_type'  => 'network',
                'provider_name'  => $input['iin']['network'],
                'interoperable'  => true,
                'status'         => 'activated',
                'provider_data'  => [
                    'token_reference_number'     => $token,
                    'payment_account_reference'  => strrev($token),
                    'token_iin'                  => '453335',
                    'token_expiry_month'         => 12,
                    'token_expiry_year'          => 2021,
                ],
            ]
        ];

        return $response;
    }

    public function fetchCryptogram($input): array
    {
        $response['success'] = true;

        $dummyCardNumber = '4100000000000099';

        $response['service_providers'] = [
            [
                'type'  => 'network',
                'name'  => 'Visa',
                'data'  => [
                    'token_number' => $dummyCardNumber,
                    'cryptogram_value' => 12,
                    'expiry_month' => 12,
                    'expiry_year' => 2021,
                ],
            ]
        ];

        return $response;
    }

    public function fetchToken($input): array
    {
        $response['success'] = true;

        $token = base64_encode($input['token']);

        $response['token'] = $input['token'];
        $response['fingerprint'] = strrev($token);
        $response['status'] = 'active';

        $response['service_providers'] = [
            [
                'type'  => 'network',
                'name'  => 'visa',
                'data'  => [
                    'token_reference_number'     => $token,
                    'payment_account_reference'  => strrev($token),
                    'interoperable'              => true,
                ]
            ]
        ];

        return $response;
    }

    public function deleteNetworkToken($input): array
    {
        $response['success'] = true;

        return $response;
    }

    public function updateToken($input): array
    {
        $response['success'] = true;

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

    public function saveCardMetaData($input)
    {
        return [];
    }

    public function getCardMetaData($input)
    {
        $response['token']        = $input['token'];
        $response['iin']          = '411111';
        $response['expiry_month'] = '02';
        $response['expiry_year']  = '2028';
        $response['name']         = 'cards';

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
