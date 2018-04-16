<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingObcRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingObcGatewayFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_obc_terminal');

        $this->obcPaymentArray = $this->getDefaultNetbankingPaymentArray('ORBC');
    }

    public function testGenerateRefundFile()
    {
        Mail::fake();

        $payment1 = $this->doAuthAndCapturePayment($this->obcPaymentArray);

        $fullRefund = $this->refundPayment($payment1['id']);

        $payment2 = $this->doAuthAndCapturePayment($this->obcPaymentArray);

        $partialRefund = $this->refundPayment($payment2['id'], 100);

        $this->ba->adminAuth();

        $this->startTest();
    }
}
