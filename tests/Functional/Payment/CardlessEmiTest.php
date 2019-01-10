<?php


use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardlessEmiTest extends TestCase
{
    use PaymentTrait;

    const PROVIDER = 'earlysalary';

    public function setUp()
    {
        // $this->testDataFilePath = __DIR__.'/CardlessEmiGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'cardless_emi';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_cardless_emi_terminal');

        $this->fixtures->merchant->enableCardlessEmi('10000000000000');
    }

    public function testCardlessEmiInvalidInput()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray(self::PROVIDER);

        unset($payment['contact']);

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        BadRequestValidationFailureException::class,
        'The contact field is required.');

        $payment['contact'] = '+1234-(456)-(789)';

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        BadRequestException::class,
        'Contact number needs to be Indian.');

        unset($payment['provider']);

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        BadRequestValidationFailureException::class,
        'The provider field is required when method is cardless_emi.');
    }

    public function testCardlessEmiIncorrectOtt()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray(self::PROVIDER);
        $payment['ott'] = '123456';

        $this->setOtp('123456');

        $key = 'payment:cardlessemi.123456.token';
        $data = [
            'contact'  => '9918899029',
            'provider' => 'EARLYSALARY',
        ];

        $emiPlans = $this->app['cache']->set($key, $data, 15);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            RZP\Exception\BadRequestException::class,
            'Emi duration is not valid');
    }

}
