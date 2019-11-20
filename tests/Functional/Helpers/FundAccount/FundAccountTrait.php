<?php

namespace RZP\Tests\Functional\Helpers\FundAccount;

use RZP\Models\FundAccount\Type;

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

    protected function buildFundAccountRequest($type = Type::BANK_ACCOUNT)
    {
        if ($type === Type::VPA)
        {
            $fundAccount = $this->getDefaultFundAccountVPAArray();
        }
        else
        {
            $fundAccount = $this->getDefaultFundAccountBankAccountArray();
        }

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

        $request = $this->buildFundAccountRequest(Type::BANK_ACCOUNT);

        $this->ba->privateAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        $expectedFundAccount = $this->getDefaultFundAccountBankAccountArray();

        $this->assertArraySelectiveEquals($expectedFundAccount, $content);

        return $content;
    }

    protected function createFundAccountVpa($key = null)
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $request = $this->buildFundAccountRequest(Type::VPA);

        $this->ba->privateAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        $expectedFundAccount = $this->getDefaultFundAccountVPAArray();

        $this->assertArraySelectiveEquals($expectedFundAccount, $content);

        return $content;
    }

    protected function getDefaultFundAccountVPAArray()
    {
        return [
            'account_type' => 'vpa',
            'contact_id'   => 'cont_1000000contact',
            'details'      => [
                "address" => "withname@razorpay"
            ],
        ];
    }
}
