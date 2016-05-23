<?php

namespace Tests\Functional\Merchant;

use Carbon\Carbon;
use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

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
}