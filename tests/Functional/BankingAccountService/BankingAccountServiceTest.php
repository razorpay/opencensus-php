<?php

namespace RZP\Tests\Functional\BankingAccountService;

use App;

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccountService\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class BankingAccountServiceTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected $config;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BankingAccountServiceTestData.php';

        parent::setUp();

        $this->ba->bankingAccountServiceAppAuth();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->config = App::getFacadeRoot()['config'];
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

    public function testCron()
    {
        $this->ba->cronAuth();

        $response = $this->startTest();

        $this->assertEquals('ACTIVE', $response['data']['status']);
    }

    public function testVendorPaymentCompositeExpands()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->create('user', ['id' => '10000000000000', 'name' => 'test-me']);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->create('contact', ['id' => 'Dsp92d4N1Mmm6Q', 'name' => 'test_contact']);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => 'D6Z9Jfir2egAUT',
                                    'source_type' => 'contact',
                                    'source_id'   => 'Dsp92d4N1Mmm6Q',
                                    'merchant_id' => '10000000000000'
                                ]);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => 'D6Z9Jfir2egAUD',
                                    'source_type' => 'contact',
                                    'source_id'   => 'Dsp92d4N1Mmm6Q',
                                    'merchant_id' => '10000000000000'
                                ]);

        $this->fixtures->create('payout', ['id' => 'DuuYxmO7Yegu3x', 'fund_account_id' => 'D6Z9Jfir2egAUT','pricing_rule_id' => '1nvp2XPMmaRLxb']);

        //overwriting the balance entity to match conditions required to call mocked bas method.
        $this->fixtures->edit('balance', '10000000000000',
                              [
                                  'balance'      => 1000,
                                  'type'         => 'banking',
                                  'account_type' => 'direct',
                                  'channel'      => 'icici',
                              ]);

        $this->startTest();
    }
}
