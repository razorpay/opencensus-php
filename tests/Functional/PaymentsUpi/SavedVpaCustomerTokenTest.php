<?php

namespace RZP\Tests\Functional\PaymentsUpi;

use DB;
use Mail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\PaymentsUpiTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SavedVpaCustomerTokenTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/SavedVpaCustomerTokenTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['tokens', 'cardsaving', 'save_vpa']);

        $this->fixtures->create('customer:upi_payments_local_customer_token');

        $this->fixtures->create('customer:upi_payments_global_customer_token');

        $this->createUpiPaymentsGlobalCustomerVpa();
    }

    public function testGetCustomerTokensWithSaveVpaFeatureEnabled()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCustomerTokensWithSaveVpaFeatureDisabled()
    {
        $this->fixtures->merchant->removeFeatures(['save_vpa']);

        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    protected function mockSession()
    {
        $data = array(
            'test_app_token'   => 'capp_1000000custapp',
            'test_checkcookie' => '1'
        );

        $this->session($data);
    }
}
