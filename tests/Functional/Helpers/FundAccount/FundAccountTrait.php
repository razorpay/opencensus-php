<?php

namespace RZP\Tests\Functional\Helpers\FundAccount;

trait FundAccountTrait
{
    private function getDefaultFundAccountBankAccountArray()
    {
        return [
            'account_type' => 'bank_account',
            'contact_id'   => 'cont_1000000contact',
            'details'      => [
                'ifsc_code'        => 'SBIN0007105',
                'beneficiary_name' => 'Amit M',
                'account_number'   => '111000111',
            ],
        ];
    }

    private function getDefaultFundAccountBankAccountArrayResponse()
    {
        return [
            'account_type' => 'bank_account',
            'contact_id'   => 'cont_1000000contact',
            'details'      => [
                'ifsc'        => 'SBIN0007105',
                'name' => 'Amit M',
                'account_number'   => '111000111',
            ],
        ];
    }

    private function buildFundAccountBankAccountRequest()
    {
        $fundAccount = $this->getDefaultFundAccountBankAccountArray();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => $fundAccount,
        ];

        return $request;
    }

    private function createFundAccountBankAccount($key = null)
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $request = $this->buildFundAccountBankAccountRequest();

        $this->ba->privateAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        $expectedFundAccount = $this->getDefaultFundAccountBankAccountArrayResponse();

        $this->assertArraySelectiveEquals($expectedFundAccount, $content);

        return $content;
    }
}
