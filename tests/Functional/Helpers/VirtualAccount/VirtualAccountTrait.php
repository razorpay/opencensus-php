<?php

namespace RZP\Tests\Functional\Helpers\VirtualAccount;

trait VirtualAccountTrait
{
    private function createVirtualAccount($input = [])
    {
        $defaultValues = $this->getDefaultVirtualAccountArray();

        $attributes = array_merge($defaultValues, $input);

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => $attributes,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function closeVirtualAccount($id)
    {
        $request = [
            'method'  => 'PATCH',
            'url'     => '/virtual_accounts/'.$id,
            'content' => [
                'status' => 'closed',
            ],
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function deleteVirtualAccount($id)
    {
        $request = [
            'method'  => 'DELETE',
            'url'     => '/virtual_accounts/'.$id,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchVirtualAccount($id)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/virtual_accounts/' . $id,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchVirtualAccounts($input = [])
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/virtual_accounts',
            'content' => $input,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getDefaultVirtualAccountArray()
    {
        return [
            'name'            => 'New virtual account',
            'descriptor'      => 'banana',
            'amount_expected' => 10000,
            'receiver_type'   => 'bank_account',
        ];
    }
}
