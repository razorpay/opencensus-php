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

    private function fetchVirtualAccountPayments(string $id)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/virtual_accounts/' . $id . '/payments',
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function refundVirtualAccountExcessPayments()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/refund/excess',
        ];

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function payVirtualAccount(string $virtualAccountId, array $paymentArray = [])
    {
        $defaultPaymentArray = $this->getDefaultBankTransferArray();

        $paymentArray = array_merge($defaultPaymentArray, $paymentArray);

        $response = $this->fetchVirtualAccount($virtualAccountId);

        $bankAccount = $response['receivers'][0];

        $paymentArray['payee_account'] = $bankAccount['account_number'];
        $paymentArray['payee_ifsc']    = $bankAccount['ifsc'];

        $request = [
            'method'  => 'POST',
            'url'     => '/ecollect/validate',
            'content' => $paymentArray,
        ];

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getDefaultBankTransferArray()
    {
        return [
            'payer_account'  => '7654321234567',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => time(),
            'amount'         => 100,
            'description'    => 'Test bank transfer',
        ];
    }

    private function getDefaultVirtualAccountArray()
    {
        return [
            'name'            => 'Test virtual account',
            'description'     => 'VA for tests',
            'receiver_types'  => [
                'bank_account'
            ],
            'notes'           => [
                'a' => 'b',
            ],
        ];
    }
}
