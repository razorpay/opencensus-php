<?php

namespace RZP\Tests\Functional\Gateway\File;

use Carbon\Carbon;
use Mail;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class AirtelMoneyFailedRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/AirteMoneyFailedRefundFileTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_airtelmoney_terminal');

        $this->gateway = 'wallet_airtelmoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
    }


    public function testAirtelMoneyFailedRefundFile()
    {
         Mail::fake();

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $paymentId1 = $this->doAuthAndCapturePayment($payment);

        $paymentId2 = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($paymentId1['id'], 10000);

        $this->refundPayment($paymentId1['id'], 10000);

        $this->refundPayment($paymentId2['id']);

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['status' => 'failed']);
        }

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

         $expectedFileContent = [
            'type'        => 'airtelmoney_wallet_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'xlsx',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(RefundFileMail::class);
    }

}
