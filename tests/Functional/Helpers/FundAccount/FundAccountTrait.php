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

    protected function buildFundAccountRequest($type = Type::BANK_ACCOUNT, $vpaId = null)
    {
        if ($type === Type::VPA)
        {
            $fundAccount = $this->getDefaultFundAccountVPAArray($vpaId);
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

    protected function createFundAccountBankAccount($key = null, $mode = 'test')
    {
        $this->fixtures->on($mode)->create('contact', ['id' => '1000000contact']);

        $request = $this->buildFundAccountRequest(Type::BANK_ACCOUNT);

        $this->ba->privateAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        $expectedFundAccount = $this->getDefaultFundAccountBankAccountArray();

        $this->assertArraySelectiveEquals($expectedFundAccount, $content);

        return $content;
    }

    protected function createFundAccountVpa($key = null, $vpaId = 'withname@razorpay')
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $request = $this->buildFundAccountRequest(Type::VPA, $vpaId);

        $this->ba->privateAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        $expectedFundAccount = $this->getDefaultFundAccountVPAArray($vpaId);

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

        $expectedFundAccount['card'] = [];

        $this->assertArraySelectiveEquals($expectedFundAccount, $content);

        return $content;
    }

    protected function getDefaultFundAccountVPAArray($vpaId = 'withname@razorpay')
    {
        return [
            'account_type' => 'vpa',
            'contact_id'   => 'cont_1000000contact',
            'vpa'      => [
                "address" => $vpaId
            ],
        ];
    }

    protected function getDefaultFundAccountCardArray()
    {
        return [
            'account_type' => 'card',
            'contact_id'   => 'cont_1000000contact',
            'card' => [
                'name' => 'jp',
                'number' => '4111111111111111',
                'expiry_month' => 4,
                'expiry_year' => 2025
            ]
        ];
    }
}
