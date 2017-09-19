<?php

namespace RZP\Tests\Functional\Gateway\File;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GatewayEmiFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/GatewayEmiFileTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockTokenex();
    }

    public function testGenerateEmiFile()
    {
        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4111460212312338', 3);

        $this->ba->appAuth();

        $content = $this->startTest();
    }

    protected function makeEmiPaymentOnCard($card, $emiDuration,
        $save = 0, $appToken = null, $customerId = null, $merchantSubvention = false)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = 500000;
        $payment['method'] = 'emi';
        $payment['emi_duration'] = $emiDuration;
        $payment['card']['number'] = $card;
        $payment['save'] = $save;
        $payment['app_token'] = $appToken;
        $payment['customer_id'] = $customerId;

        if ($merchantSubvention === true)
        {
            $this->fixtures->merchant->addFeatures(['emi_merchant_subvention']);
        }

        $this->doAuthAndCapturePayment($payment);
    }
}
