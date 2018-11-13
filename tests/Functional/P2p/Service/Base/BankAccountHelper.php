<?php

namespace RZP\Tests\P2p\Service\Base;

class BankAccountHelper extends P2pHelper
{
    public function fetchBanks()
    {
        $this->validationJsonSchemaPath = 'bank_account/bank/fetch_all';

        $this->isCustomerInContext = false;

        $request = $this->request('banks');

        $this->isCustomerInContext = true;

        return $this->get($request);
    }

    public function fetch(string $bankId)
    {
        $this->validationJsonSchemaPath = 'bank_account/fetch';

        $request = $this->request('bank_accounts/%s', [$bankId]);

        return $this->get($request);
    }

    public function retrieve(string $ifsc)
    {
        $this->validationJsonSchemaPath = 'bank_account/retrieve';

        $request = $this->request('bank_accounts/bank/%s', [$ifsc]);

        return $this->get($request);
    }

    public function setUpiPin(string $bankId, array $content = [])
    {
        $this->validationJsonSchemaPath = 'bank_account/set_upi_pin';

        $request = $this->request('bank_accounts/%s/upi_pin', [$bankId]);

        $default = [
            'cl.creds' => [
                [
                    'code'     => 'NPCI',
                    'ki'       => '20150822',
                    'string'   => '2.0|QNSo1fHj5iTFseh6RlfZh9u/bX5AyYiVYCTUMYXzd+g==',
                    'sub_type' => 'MPIN',
                    'type'     => 'PIN'
                ],
                [
                    'code'     => 'NPCI',
                    'ki'       => '20150822',
                    'string'   => '2.0|UJ5I2VFkAt0lA0qZ0J0EbXKSprXThhuQqPl5yQlzs3/A==',
                    'sub_type' => 'SMS',
                    'type'     => 'OTP'
                ],
            ],
            'card' => [
                'expiry_month' => 2,
                'expiry_year'  => 19,
                'last6'        => '123456'
            ],
            'transaction_id' => 'RAZ18FCE7E4597443C7963B999CCD70C869',
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateSetUpiPin(string $bankId)
    {
        $this->validationJsonSchemaPath = 'bank_account/initiate_set_upi_pin';

        $request = $this->request('bank_accounts/%s/upipin/initiate', [$bankId]);

        return $this->get($request);
    }

    public function initiateFetchBalance(string $bankId)
    {
        $this->validationJsonSchemaPath = 'bank_account/initiate_fetch_balance';

        $request = $this->request('bank_accounts/%s/balance/initiate', [$bankId]);

        return $this->get($request);
    }

    public function fetchBalance(string $bankId, array $content = [])
    {
        $this->validationJsonSchemaPath = 'bank_account/fetch_balance';

        $request = $this->request('bank_accounts/%s/balance', [$bankId]);

        $default = [
            'cl.creds' => [
                [
                    'code'     => 'NPCI',
                    'ki'       => '20150822',
                    'string'   => '2.0|VgZY8no118pbrVQj5yPI64jLTNHdICiv6fRr7vbBjxSWPg==',
                    'sub_type' => 'MPIN',
                    'type'     => 'PIN'
                ]
            ],
            'transaction_id' => 'RAZ18FCE7E4597443C7963B999CCD70C869',
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
