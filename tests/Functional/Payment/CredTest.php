<?php

use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CredTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = 'cred';

        $this->sharedTerminal = $this->fixtures->create('terminal:direct_cred_terminal');

        $this->fixtures->merchant->enableCred('10000000000000');
    }

    public function testCredPaymentCreateResponseIntentFlow()
    {
        $payment = $this->getDefaultCredPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];
        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('intent', $response['type']);

        $this->assertEquals('cred://pay?am=100.00&cu=INRPAISE&mc=5411', $response['data']['intent_url']);

    }

    public function testCredPaymentCreateResponseCollectFlow()
    {
        $payment = $this->getDefaultCredPayment();

        $payment['cred']['app_present'] = false;

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('async', $response['type']);

        $this->assertEquals('cred_merchant', $response['data']['vpa']);
    }
}
