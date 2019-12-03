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
            'bank_account'      => [
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
        elseif ($type === Type::CARD)
        {
            $fundAccount = $this->getDefaultFundAccountCardArray();
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

    protected function createFundAccountCard($key = null)
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $request = $this->buildFundAccountRequest(Type::CARD);

        $this->ba->privateAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        $expectedFundAccount = $this->getDefaultFundAccountCardArray();

        $expectedFundAccount['details'] = [];

        $this->assertArraySelectiveEquals($expectedFundAccount, $content);

        return $content;
    }

    protected function getDefaultFundAccountVPAArray()
    {
        return [
            'account_type' => 'vpa',
            'contact_id'   => 'cont_1000000contact',
            'vpa'      => [
                "address" => "withname@razorpay"
            ],
        ];
    }

    protected function getDefaultFundAccountCardArray()
    {
        return [
            'account_type' => 'card',
            'contact_id'   => 'cont_1000000contact',
            'details' => [
                'name' => 'jp',
                'number' => '4111111111111111',
                'expiry_month' => 4,
                'expiry_year' => 2025
            ]
        ];
    }
}
