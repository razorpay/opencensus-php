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

    	$dt = Carbon::today('Asia/Kolkata');

    	$input = array(
    		'year' => $dt->year,
    		'month' => $dt->month,
    		'day' => $dt->day);

    	$content = $this->fetchReport('payment', $input);
        $content = $this->fetchReport('transaction', $input);
    }
}