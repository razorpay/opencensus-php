<?php

namespace RZP\Tests\Functional\BankingAccountService;

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccountService\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class BankingAccountServiceTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BankingAccountServiceTestData.php';

        parent::setUp();

        $this->ba->bankingAccountServiceAppAuth();

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testCreateBankingEntities()
    {
        $response = $this->startTest();

        $balance = $this->getDbEntity('balance',
                                          [
                                              'merchant_id'    => '10000000000000',
                                              'channel'        => 'icici',
                                              'account_type'   => 'direct',
                                              'account_number' => '12345678903833',
                                          ]);

        $this->assertNotNull($balance);

        $this->assertEquals($balance->getId(), $response['balance_id']);

        $basd = $this->getDbEntity('banking_account_statement_details',
                                       [
                                           'merchant_id'    => '10000000000000',
                                           'channel'        => 'icici',
                                           'balance_id'     => $balance->getId(),
                                           'account_number' => '12345678903833',
                                       ]);

        $this->assertNotNull($basd);
    }

    public function testCreateBusinessId()
    {
        $this->ba->proxyAuth();

        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->startTest();

        $merchantDetail = $this->getDbEntity('merchant_detail',
                                      [
                                          'bas_business_id'  => '10000000000011',
                                      ]);

        $this->assertNotNull($merchantDetail);
    }
}
