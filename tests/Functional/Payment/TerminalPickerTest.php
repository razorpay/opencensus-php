<?php

namespace RZP\Tests\Functional\Payment;

use DB;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class TerminalTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalTestData.php';

        parent::setUp();
    }

    public function testBilldeskGatewayOnSharedTerminals()
    {
        $this->fixtures->create('terminal:all_shared_terminals');

        // Create all shared terminals
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'BKID';

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('billdesk', $payment['gateway']);
        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);
    }

    public function testHdfcGatewayOnSharedTerminals()
    {
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        // $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:shared_hdfc_terminal');

        // Create all shared terminals
        $payment = $this->doAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
        $this->assertEquals('1000HdfcShared', $payment['terminal_id']);
    }

    public function testDeleteTerminalAndDoPayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create('terminal:axis_genius_terminal');

        // Make sure payment is happening
        $payment = $this->doAuthPayment();

        // Now soft delete terminal
        $content = $this->deleteTerminal($terminal['merchant_id'], $terminal['id']);

        // Ensure it's soft-deleted
        $terminals = $this->getEntities('terminal', ['deleted' => '1'], true);
        $this->assertNotNull($terminals['items'][0]['deleted_at']);

        $e = null;

        $data = $this->testData['testDeleteTerminalAndDoPayment'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            // Do a transaction which should throw an exception
            // because terminal is already soft-deleted
            $payment = $this->doAuthAndCapturePayment();
        });


        $this->fixtures->create('terminal:shared_sharp_terminal');
        $payment = $this->doAuthAndCapturePayment();
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('sharp', $payment['gateway']);
        $this->assertEquals('1000SharpTrmnl', $payment['terminal_id']);
    }
}
