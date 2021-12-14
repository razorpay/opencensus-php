<?php

namespace Functional\QrCodeConfig;

use Illuminate\Database\Eloquent\Factory;

use Carbon\Carbon;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class QrCodeConfigTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/QrCodeConfigTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    protected function enableRazorXTreatmentForQrCutoffConfig()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function(string $id, string $featureFlag, string $mode) {
                   if ($featureFlag === (RazorxTreatment::QR_CODE_CUTOFF_CONFIG))
                   {
                       return 'on';
                   }

                   return 'control';
               });
    }

    public function testProcessIciciQrPaymentCutoffTimeExceeded()
    {
        $this->enableRazorXTreatmentForQrCutoffConfig();

        $this->fixtures->create('qr_code_config');

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $response = $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntityToArray('qr_payment');
        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);

        $this->assertEquals(UnexpectedPaymentReason::QR_CODE_CUTOFF_TIME_EXCEEDED,$qrPayment['unexpected_reason']);
    }

    public function testProcessIciciQrPaymentCutoffTimeNotExceeded()
    {
        $this->enableRazorXTreatmentForQrCutoffConfig();

        $this->fixtures->create('qr_code_config');

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $currentTime = str_replace([':', '-', ' '], '', Carbon::now()->toDateTimeString());
        $request['content']['TxnCompletionDate'] = $currentTime;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntityToArray('qr_payment');
        $payment   = $this->getDbLastEntityToArray('payment');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }
}
