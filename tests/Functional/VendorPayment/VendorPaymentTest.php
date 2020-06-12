<?php

namespace RZP\Tests\Functional\VendorPayment;

use App;
use RZP\Models\Admin\Service;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class VendorPaymentTest extends TestCase
{
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected $config;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/VendorPaymentTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

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

        $this->config = App::getFacadeRoot()['config'];
    }

    public function testCompositeExpands()
    {
        // will call the compostie api and check if the response are as expected
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->create('user', ['id' => '10000000000000', 'name' => 'test-me']);

        $this->fixtures->create('contact', ['id' => 'Dsp92d4N1Mmm6Q', 'name' => 'test_contact']);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => 'D6Z9Jfir2egAUT',
                                    'source_type' => 'contact',
                                    'source_id'   => 'Dsp92d4N1Mmm6Q',
                                    'merchant_id' => '10000000000000'
                                ]);

        $this->fixtures->create('payout', ['id' => 'DuuYxmO7Yegu3x', 'fund_account_id' => 'D6Z9Jfir2egAUT']);

        $this->startTest();
    }

    public function testCompositeExpandsWhenOnlyPayoutIsPassed()
    {
        // will call the compostie api and check if the response are as expected
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->create('user', ['id' => '10000000000000', 'name' => 'test-me']);

        $this->fixtures->create('contact', ['id' => 'Dsp92d4N1Mmm6Q', 'name' => 'test_contact']);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => 'D6Z9Jfir2egAUT',
                                    'source_type' => 'contact',
                                    'source_id'   => 'Dsp92d4N1Mmm6Q',
                                    'merchant_id' => '10000000000000'
                                ]);

        $this->fixtures->create('payout', ['id' => 'DuuYxmO7Yegu3x', 'fund_account_id' => 'D6Z9Jfir2egAUT']);

        $this->startTest();
    }

    public function testCreatePayout()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $payout1 = $this->startTest();

        $payout2 = $this->startTest();

        $this->assertEquals($payout1['id'], $payout2['id']);

    }

}
