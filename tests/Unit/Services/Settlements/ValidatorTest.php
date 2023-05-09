<?php

namespace RZP\Tests\Unit\Services;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Currency\Currency;
use RZP\Tests\TestCase;
use RZP\Services\Settlements\Validator;

class ValidatorTest extends TestCase
{
    public function testCreateBankAccountRulesWithValidCurrency()
    {
        $req = $this->getBankAccountCreateRequest();
        $req['accepted_currency'] = Currency::USD;
        (new Validator)->validateInput('create_bank_account', $req);
        $this->expectNotToPerformAssertions();
    }

    public function testCreateBankAccountRulesWithInvalidCurrency()
    {
        $req = $this->getBankAccountCreateRequest();
        $req['accepted_currency'] = 'ABC';
        $this->expectException(BadRequestValidationFailureException::class);
        (new Validator)->validateInput('create_bank_account', $req);
    }

    protected function getBankAccountCreateRequest()
    {
        return [
            'merchant_id'         => '100000razorpay',
            'account_number'      => '12345678900',
            'account_type'        => 'current',
            'ifsc_code'           => 'HDFC0000001',
            'beneficiary_name'    => 'John Doe',
            'beneficiary_address' => '221B Baker Street',
            'beneficiary_city'    => 'Bengaluru',
            'beneficiary_state'   => 'KA',
            'beneficiary_country' => 'IN',
            'beneficiary_email'   => 'sample@example.com',
            'beneficiary_mobile'  => +919999999999,
            'accepted_currency'   => Currency::INR,
            'extra_info'          => [
                'via' => 'payout'
            ],
        ];
    }
}
