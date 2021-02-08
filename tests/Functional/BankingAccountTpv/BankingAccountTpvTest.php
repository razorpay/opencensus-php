<?php

namespace RZP\Tests\Functional\BankingAccountTpv;

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccountTpv\Type;
use RZP\Models\BankingAccountTpv\Entity;
use RZP\Models\BankingAccountTpv\Status;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;

class BankingAccountTpvTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BankingAccountTpvTestData.php';

        parent::setUp();
    }

    public function testAdminTpvCreate()
    {
        $this->ba->adminAuth();

        $fav = $this->getFundAccountValidationInput();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] = $fav['id'];

        $response = &$this->testData[__FUNCTION__]['response'];

        $response['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] = FundAccountValidation::verifyIdAndSilentlyStripSign($fav['id']);

        $this->startTest();

        $fav = $this->getDbEntity('fund_account_validation',
                                  [
                                      'id'          => $response['content'][Entity::FUND_ACCOUNT_VALIDATION_ID],
                                  ]);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'payer_account_number' => '98711120003344',
                                      'status'               => Status::APPROVED,
                                  ]);

        $this->assertNotNull($fav);

        $this->assertNotNull($tpv);
    }

    public function testAdminTpvCreateDuplicateException()
    {
        $attributes = $this->getTpvInput();

        $attributes[Entity::PAYER_IFSC] = 'CITI5242987';

        $this->fixtures->create('banking_account_tpv', $attributes);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetMerchantTpvs()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $attributes = $this->getTpvInput();

        $this->fixtures->create('banking_account_tpv', $attributes);

        $attributes[Entity::STATUS] = Status::REJECTED;

        $attributes[Entity::IS_ACTIVE] = false;

        $attributes[Entity::REMARKS] = 'Invalid docs';

        $this->fixtures->create('banking_account_tpv', $attributes);

        $this->ba->proxyAuth();

        $this->startTest();

    }

    public function testFetchMerchantTpvsWithNoRecords()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertTrue(empty($response->count) === true);

    }

    public function testAdminEditTpv()
    {
        $attributes = $this->getTpvInput();

        $this->fixtures->create('banking_account_tpv', $attributes);

        $this->ba->adminAuth();

        $this->startTest();

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::REJECTED,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);
    }

    public function testAdminEditTpvInvalidAccountNumber()
    {
        $attributes = $this->getTpvInput();

        $this->fixtures->create('banking_account_tpv', $attributes);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchMerchantTpvsWithFavInfo()
    {
        $this->ba->adminAuth();

        for ($i = 0; $i < 10; $i++)
        {
            $attributes = $this->getTpvInput([Entity::STATUS => rand(0, 1) ? Status::APPROVED : Status::PENDING]);

            $fav = $this->getFundAccountValidationInput();

            $attributes[Entity::FUND_ACCOUNT_VALIDATION_ID] = FundAccountValidation::verifyIdAndSilentlyStripSign($fav['id']);

            $this->fixtures->create('banking_account_tpv', $attributes);
        }

        $response = $this->startTest();

        foreach ($response['items'] as $key => $val)
        {
            $this->assertNotNull($val[Entity::STATUS]);

            $this->assertNotNull($val[Entity::FUND_ACCOUNT_VALIDATION_ID]);

            $this->assertNotNull($val[Entity::FUND_ACCOUNT_VALIDATION]);
        }
    }

    public function getFundAccountValidationInput()
    {
        $this->fixtures->create('fund_account_validation', [
            FundAccountValidation::ACCOUNT_STATUS => "active",
            FundAccountValidation::NOTES          => [
                FundAccountValidation::MERCHANT_ID => '10000000000000',
            ],
        ]);

        return $this->getLastEntity('fund_account_validation', true, 'test');

    }

    public function getTpvInput(array $input = [])
    {
        $default = [
            Entity::MERCHANT_ID          => '10000000000000',
            Entity::BALANCE_ID           => '10000000000000',
            Entity::STATUS               => Status::APPROVED,
            Entity::PAYER_NAME           => 'Razorpay',
            Entity::PAYER_ACCOUNT_NUMBER => '98711120003344',
            Entity::PAYER_IFSC           => 'CITI0000006',
            Entity::CREATED_BY           => 'OPS_A',
            Entity::TYPE                 => Type::BANK_ACCOUNT,
            Entity::IS_ACTIVE            => true,
        ];

        return array_merge($default, $input);
    }

}
