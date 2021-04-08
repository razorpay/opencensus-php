<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\FailedRefund\Base as FailedRefund;

class AirtelMoneyFailedRefundFileTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
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

        $this->ba->adminAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'airtelmoney_wallet_failed_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'csv',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(FailedRefund::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $body = 'Please find attached failed refunds information for Airtel Money';

            $fileName = 'Airtelmoney_Wallet_Failed_Refunds_test_'. $date  . '.csv';

            $subject = 'Airtel Money failed refunds file for ' . $date;

            $this->assertEquals($subject, $mail->subject);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($fileName, $mail->viewData['file_name']);

            return true;
        });
    }
}
