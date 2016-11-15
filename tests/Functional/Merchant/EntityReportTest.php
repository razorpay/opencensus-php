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
}
