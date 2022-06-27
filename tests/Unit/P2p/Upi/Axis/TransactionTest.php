<?php

namespace RZP\Tests\Unit\P2p\Upi\Axis;

use RZP\Constants\Mode;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\Service;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\P2p\Service\Base\Constants;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;
use RZP\Tests\P2p\Service\Base\Traits\MetricsTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class TransactionTest extends TestCase
{
    use MetricsTrait;
    use TransactionTrait;
    /**
     * @var Context
     */
    protected $context;

    protected $action;

    protected $mode = Mode::TEST;

    protected $gateway = 'p2p_upi_axis';

    protected $entity = 'transaction';

    protected $gatewayInput;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setContext();

        $this->gatewayInput = new ArrayBag();
    }

    public function testAmountValidator()
    {
        $this->now($this->now()->subDays(2));

        $this->createCompletedPayTransaction();

        $this->now($this->testCurrentTime);

        $service = $this->getService();

        $response = $service->initiatePay([
            'amount'    => 10000000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
            ],
            'payee'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_2)->getPublicId(),
            ]
        ]);

        $this->assertSame('100000.00', $response['request']['content']['amount']);
    }

    public function testFirstTransactionAboveLimit()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('First transaction can not be more than 5000 rupees.');

        $this->getService()->initiatePay([
            'amount'    => 10000000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
            ],
            'payee'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_2)->getPublicId(),
            ]
        ]);
    }

    public function testCoolDownPeriodSuccess()
    {
        $this->now($this->now()->subHour(2));

        $this->createCompletedPayTransaction([
            Entity::AMOUNT => 400000
        ]);

        $this->now($this->testCurrentTime);

        $response = $this->getService()->initiatePay([
            'amount'    => 100000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
            ],
            'payee'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_2)->getPublicId(),
            ]
        ]);

       $this->assertSame('1000.00', $response['request']['content']['amount']);

    }

    public function testCoolDownPeriodFailure()
    {

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Allowed limit of 5000 exceeded in cooldown of 24 hours.');

        $this->now($this->now()->subHour(2));

        $this->createCompletedPayTransaction([
            Entity::AMOUNT => 450000
        ]);

        $this->now($this->testCurrentTime);

        $this->getService()->initiatePay([
            'amount'    => 100000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
            ],
            'payee'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_2)->getPublicId(),
            ]
        ]);

    }

    public function testFirstDayTransaction()
    {
        $this->now($this->now()->subHour(2));

        $this->createCompletedPayTransaction([ Entity::AMOUNT => 100000 ]);

        $this->createCollectPendingTransaction([ Entity::AMOUNT => 100000 ]);

        $this->createCompletedPayTransaction([ Entity::AMOUNT => 100000 ]);

        $this->createCompletedPayTransaction([ Entity::AMOUNT => 100000 ]);

        $this->now($this->testCurrentTime);

        $response = $this->getService()->initiatePay([
            'amount'    => 100000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
            ],
            'payee'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_2)->getPublicId(),
            ]
        ]);

        $this->assertSame('1000.00', $response['request']['content']['amount']);

    }

    public function testCreditDebitTransactions()
    {
        $this->now($this->now()->subHour(2));

        $this->createCompletedPayTransaction([ Entity::AMOUNT => 100000 ]);

        $this->createPayIncomingTransaction([
            Entity::AMOUNT => 400000
        ]);

        $this->now($this->testCurrentTime);

        $response = $this->getService()->initiatePay([
            'amount'    => 100000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
            ],
            'payee'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_2)->getPublicId(),
            ]
        ]);

        $this->assertSame('1000.00', $response['request']['content']['amount']);
    }

    // TODO: Add test case for collect and authorize flow
    public function testCollectFlow()
    {
        $transaction = $this->createCollectIncomingTransaction([
            Entity::AMOUNT => 100000
        ]);

        $response = $this->getService()->initiateAuthorize([
            Entity::ID => $transaction->getPublicId()
        ]);

        $this->assertSame('1000.00', $response['request']['content']['amount']);
    }

    public function testCollectFailFlow()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('First transaction can not be more than 5000 rupees.');

        $transaction = $this->createCollectIncomingTransaction([
            Entity::AMOUNT => 600000
        ]);

        $this->getService()->initiateAuthorize([
            Entity::ID => $transaction->getPublicId()
        ]);
    }

    public function testCollectExceeds()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Allowed limit of 5000 exceeded in cooldown of 24 hours.');

        $this->createCompletedPayTransaction([
            Entity::AMOUNT => 450000
        ]);

        $transaction = $this->createCollectIncomingTransaction([
            Entity::AMOUNT => 100000
        ]);

        $this->getService()->initiateAuthorize([
            Entity::ID => $transaction->getPublicId()
        ]);
    }

    public function testCollectPassAfterCooldown()
    {
        $this->now($this->now()->subDays(2));

        $this->createCompletedPayTransaction([
            Entity::AMOUNT => 450000
        ]);

        $this->now($this->testCurrentTime);

        $transaction = $this->createCollectIncomingTransaction([
            Entity::AMOUNT => 100000
        ]);

        $response = $this->getService()->initiateAuthorize([
            Entity::ID => $transaction->getPublicId()
        ]);

        $this->assertSame('1000.00', $response['request']['content']['amount']);
    }

    public function testCollectAmountExceeds()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Maximum per collect transaction limit is Rs 2000.');

        $transaction = $this->createCollectTransaction([
            Entity::AMOUNT => 300000
        ]);

        $this->getService()->initiateAuthorize([
            Entity::ID => $transaction->getPublicId()
        ]);
    }

    public function testCollectAmountExceedsForDebitFlow()
    {
        $transaction = $this->createCollectIncomingTransaction([
            Entity::AMOUNT => 300000
        ]);

        $this->getService()->initiateAuthorize([
            Entity::ID => $transaction->getPublicId()
        ]);
    }

    public function testCollectRequestsPerDayExceed()
    {
        $maxCollectRequestsAllowedPerDay = 5;

        // Creating allowed number of collect requests first
        for ($i = 0; $i < $maxCollectRequestsAllowedPerDay; $i++)
        {
            $this->createCollectTransaction([
                Entity::AMOUNT => 100000,
            ]);
        }

        // Expecting exception for next collect request
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('You have exceeded the allowable limit of collect request generation. Please try after 24 hours');

        $transaction = $this->createCollectTransaction([
            Entity::AMOUNT => 100000,
        ]);

        $this->getService()->initiateAuthorize([
            Entity::ID => $transaction->getPublicId()
        ]);
    }

    /**
     * same device with different vpa and different bank account
     */
    public function testSelfTransfer()
    {
        $service = $this->getService();

        $payerVpa = $this->fixtures->vpa(self::DEVICE_1);

        $payeeVpa = $this->fixtures->createVpa([
            'bank_account_id' => Constants::CUSTOMER_2_BANK_ACCOUNT_1_AXIS,
        ]);

        $this->assertNotEquals($payeeVpa->getId(), $payerVpa->getId());
        $this->assertNotEquals($payeeVpa->getBankAccountId(), $payerVpa->getBankAccountId());

        $response = $service->initiatePay([
            'amount'    => 500000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $payerVpa->getPublicId(),
            ],
            'payee'     => [
                'id'    => $payeeVpa->getPublicId(),
            ],
        ]);

        $this->assertSame('5000.00', $response['request']['content']['amount']);
    }

    /**
     * Same vpa transfer in the same device should throw exception
     */
    public function testSelfTransferSameVpaIdException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Payer and Payee should not be same');

        $service = $this->getService();

        $service->initiatePay([
            'amount'    => 500000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
            ],
            'payee'     => [
                'id'    => $this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
            ],
        ]);
    }

    /**
     * Different vpa with same bank account in the same device should throw exception
     */
    public function testSelfTransferSameAccountException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Payer and Payee bank account should not be same');

        $payerVpa = $this->fixtures->vpa(self::DEVICE_1);

        $payeeVpa = $this->fixtures->createVpa([]);

        $this->assertEquals($payeeVpa->getBankAccountId(), $payerVpa->getBankAccountId());

        $service = $this->getService();

        $service->initiatePay([
            'amount'    => 500000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $payerVpa->getPublicId(),
            ],
            'payee'     => [
                'id'    => $payeeVpa->getPublicId(),
            ],
        ]);
    }

    public function testTransactionMetricForSelfPay()
    {
        $this->mockMetric();

        $service = $this->getService();

        $payerVpa = $this->fixtures->vpa(self::DEVICE_1);

        $payeeVpa = $this->fixtures->createVpa([
            'bank_account_id' => Constants::CUSTOMER_2_BANK_ACCOUNT_1_AXIS,
        ]);

        $service->initiatePay([
            'amount'    => 500000,
            'currency'  => 'INR',
            'payer'     => [
                'id'    => $payerVpa->getPublicId(),
            ],
            'payee'     => [
                'id'    => $payeeVpa->getPublicId(),
            ],
        ]);

        $this->assertCountMetric(TransactionMetric::PSP_TRANSACTION_TOTAL, [
            TransactionMetric::DIMENSION_TYPE               => 'pay',
            TransactionMetric::DIMENSION_FLOW               => 'debit',
            TransactionMetric::DIMENSION_IS_SELF_TRANSFER   =>  true,
            TransactionMetric::DIMENSION_PREVIOUS_STATUS    =>  null,
        ]);
    }

    /**
     * Test case to verify if the verfied key is returned in the collect response
     */
    public function testVerifiedFlagIncomingCollect()
    {
        $payee = $this->fixtures->vpa(self::DEVICE_2);

        $payee->setVerified(true);

        $this->createCollectIncomingTransaction([
            Entity::AMOUNT      => 111,
            Entity::PAYEE_ID    => $payee->getId(),
        ]);

        $response = $this->getService()->fetchAll(['expand'=>['payee']]);

        $payee = $response['items'][0]['payee'];

        $this->assertTrue($payee['verified']);
    }

    protected function getService()
    {
        return new Service();
    }

    protected function setContext()
    {
        $context = new Context();

        $context->setHandle($this->fixtures->handle(self::DEVICE_1));

        $context->setMerchant($this->fixtures->merchant(self::DEVICE_1));

        $context->setDevice($this->fixtures->device(self::DEVICE_1));

        $context->setDeviceToken($this->fixtures->deviceToken(self::DEVICE_1));

        $context->registerServices();

        $this->context = $context;

        $this->app['p2p.ctx'] = $context;
    }
}
