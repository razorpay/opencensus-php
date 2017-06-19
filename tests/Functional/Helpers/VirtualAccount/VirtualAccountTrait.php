<?php

namespace RZP\Tests\Functional\Helpers\VirtualAccount;

trait VirtualAccountTrait
{
    private function createVirtualAccount(array $input = [])
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

    private function closeVirtualAccount(string $id)
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

    private function deleteVirtualAccount(string $id)
    {
        $request = [
            'method'  => 'DELETE',
            'url'     => '/virtual_accounts/'.$id,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchVirtualAccount(string $id)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/virtual_accounts/' . $id,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchVirtualAccounts(array $input = [])
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

    private function payVirtualAccount(string $virtualAccountId, array $paymentArray = [])
    {
        $defaultPaymentArray = $this->getDefaultBankTransferArray();

        $paymentArray = array_merge($defaultPaymentArray, $paymentArray);

        $response = $this->fetchVirtualAccount($virtualAccountId);

        $paymentArray['payee_account'] = $response['bank_account']['account_number'];
        $paymentArray['payee_ifsc']    = $response['bank_account']['ifsc'];

        $request = [
            'method'  => 'POST',
            'url'     => '/ecollect/validate',
            'content' => $paymentArray,
        ];

        $vvsSecret = \Config::get('applications.vvs.secret');

        $this->ba->appAuth('rzp_test', $vvsSecret);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getDefaultBankTransferArray()
    {
        return [
            'payer_account'  => '7654321234567',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => 'utr_'.rand(10000000,99999999),
            'time'           => time(),
            'amount'         => 10000,
            'description'    => 'Test bank transfer',
        ];
    }

    private function getDefaultVirtualAccountArray()
    {
        return [
            'name'            => 'Test virtual account',
            'amount_expected' => 10000,
            'receiver_type'   => 'bank_account',
            'notes'           => [
                'a' => 'b',
            ],
        ];
    }
}
