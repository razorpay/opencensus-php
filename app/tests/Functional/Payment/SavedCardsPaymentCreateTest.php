<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class SavedCardPaymentCreateTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockTokenex();
    }

    public function testLocalSavedCardPaymentCreate()
    {
        $this->payment['card'] = array(
            'cvv'  => 111
        );

        $this->payment['token'] = '10000cardtoken';
        $this->payment['customer_id'] = '100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);
    }

    public function testGlobalSavedCardPaymentCreate()
    {
        $this->markTestSkipped();

        $this->payment['card'] = array(
            'cvv'  => 111
        );

        $this->payment['token'] = '1000gcardtoken';
        $this->payment['app_id'] = '1000000custapp';

        $content = $this->doAuthAndCapturePayment($this->payment);
    }

    public function testPaymentCreateAndSaveCardLocal()
    {
        $this->payment['save'] = 1;

        $this->payment['customer_id'] = '100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);
    }

    public function testPaymentCreateAndSaveCardGlobal()
    {
        $this->markTestSkipped();

        $this->payment['save'] = 1;

        $this->payment['app_id'] = '1000000custapp';

        $content = $this->doAuthAndCapturePayment($this->payment);
    }
}