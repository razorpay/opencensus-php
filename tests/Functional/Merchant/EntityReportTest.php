<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EntityReportTest extends TestCase
{
    use PaymentTrait;

    public function testEntityReports()
    {
        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        $dt = Carbon::today('Asia/Kolkata');

        $input = array(
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day);

        $paymentReport = $this->fetchReport('payment', $input);
        $refundReport =  $this->fetchReport('refund', $input);
        $combinedReport = $this->fetchReport('transaction', $input);

        assert((count($paymentReport) + count($refundReport)) === count($combinedReport));
    }

    public function testInvoice()
    {
        // We need to setup a terminal for the netbanking payment
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        // We need an auth-refunded payment and make sure
        // that it doesn't appear in the invoice

        $payment = $this->defaultAuthPayment($this->getDefaultNetbankingPaymentArray());
        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['id'], $input);

        $dt = Carbon::today('Asia/Kolkata');
        $input = [
            'year' => $dt->year,
            'month'=> $dt->month
        ];

        $invoice = $this->fetchInvoice($input);

        $this->assertEquals($invoice['total_fee'], '2300');
        $this->assertEquals($invoice['tax'], '300');
        $this->assertEquals($invoice['razorpay_fee'], 2000);
    }

    public function testBrokingReport()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->merchant->addFeatures(['broking_report']);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $this->doAuthAndCapturePayment($payment);
        $this->doAuthCaptureAndRefundPayment($payment);

        $dt = Carbon::today('Asia/Kolkata');

        $input = array(
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day);

        $combinedReport = $this->fetchBrokingReport($input);

        $this->assertEquals(count($combinedReport), 3);

        $expectedContent = [
            // 'Merchant Name' => 'ut',
            'Merchant ID' => '10000000000000',
            // 'Txn Id' => 'pay_6w6bmFIqLiOGVj',
            'Txn State' => 'Sale',
            // 'Txn Date' => '2016-12-23 03:28',
            'Client Code' => null,
            'Merchant Txn Id' => null,
            'Product' => 'NSE',
            'Discriminator' => 'NB',
            'Bank Name' => 'ICICI Bank Ltd',
            'Card Type' => null,
            'Card No' => null,
            'Card Issuing Bank' => null,
            // 'Bank Ref No' => 'GJZMBHNV9O',
            'Gross Txn Amount' => 500,
            'Txn Charges' => 12.5,
            'Service Tax' => 1.76,
            'SB Cess' => 0.06,
            'Krishi Kalyan Cess' => 0.06,
            'Total Chargeable' => 14.38,
            'Net Amount' => 485.62,
            'Payment Status' => null,
            'Settlement Date' => null,
            'Refund Reference' => null,
            'Refund Status' => null,
        ];

        $this->assertArraySelectiveEquals($expectedContent, $combinedReport[0]);
    }

    protected function fetchBrokingReport($content)
    {
        $request = array(
            'url' => '/reports/transaction/broking',
            'method' => 'get',
            'content' => $content);

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
