<?php

namespace RZP\Tests\Functional\Helpers\FundAccount;

trait FundAccountTrait
{
    protected function getDefaultFundAccountBankAccountArray()
    {
        return [
            'account_type' => 'bank_account',
            'contact_id'   => 'cont_1000000contact',
            'details'      => [
                'ifsc'           => 'SBIN0007105',
                'name'           => 'Amit M',
                'account_number' => '111000111',
            ],
        ];
    }

    protected function buildFundAccountBankAccountRequest()
    {
        $fundAccount = $this->getDefaultFundAccountBankAccountArray();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => $fundAccount,
        ];

        return $request;
    }

    protected function createFundAccountBankAccount($key = null)
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $request = $this->buildFundAccountBankAccountRequest();

        $this->ba->privateAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        $expectedFundAccount = $this->getDefaultFundAccountBankAccountArray();

        $this->assertArraySelectiveEquals($expectedFundAccount, $content);

        return $content;
    }
}
