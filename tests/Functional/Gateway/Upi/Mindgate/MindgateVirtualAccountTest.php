<?php

namespace RZP\Tests\Functional\Gateway\Upi\Mindgate;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Models\VirtualAccount\Entity;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Trace\TraceCode;

class MindgateVirtualAccountTest extends TestCase
{
    use PaymentTrait;
    use VirtualAccountTrait;
    use DbEntityFetchTrait;

    /**
     * @var Terminal\Entity
     */
    protected $terminal = null;

    /**
     * @var Entity
     */
    protected $va = null;

    protected $input = [
        'customer_id'       => 'cust_100000customer',
        'description'       => 'Upi Transaction',
        'amount_expected'   => 12388,
        'receivers'         => [
            'qr_code'       => [
                'method'    => [
                    'card'  => false,
                    'upi'   => true,
                ]
            ],
            'types'         => [
                'qr_code',
            ],
        ]
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'upi_mindgate';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_intent_terminal');
    }

    public function testCreate()
    {
        $this->fixtures->merchant->setCategory('1111');

        $response = $this->createVirtualAccount($this->input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $this->assertSame($response['id'], $this->va->getPublicId());

        $this->assertQrString($this->va->qrCode->qr_string, $this->va);

        $filename = storage_path('files/qrcodes/' . $this->va->qrCode->getId() . '.jpeg');
    }

    public function testCreateWithInvalidReceivers()
    {
        $receivers = [
            [
                'qr_code'       => [
                    'method'    => [
                        'card'  => true,
                        'upi'   => true,
                    ]
                ],
                'types'         => [
                    'qr_code',
                ],
            ],
            [
                'qr_code'       => [
                    'method'    => [
                        'card'  => true,
                        'upi'   => false,
                    ]
                ],
                'types'         => [
                    'qr_code',
                ],
            ],
        ];

        foreach ($receivers as $receiver)
        {
            $this->input['receivers'] = $receiver;

            $this->makeRequestAndCatchException(function()
            {
                $this->createVirtualAccount($this->input);
            },
            Exception\BadRequestException::class);

            $va = $this->getDbLastEntity('virtual_account');

            // No VA is created
            $this->assertNull($va);
        }
    }

    public function testCreateWithWrongReceiver()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $receivers = [
            [
                'qr_code'       => [
                    'method'    => [
                        'card'  => false,
                        'upi'   => true,
                    ]
                ],
                'types'         => [
                    'bank_account',
                ],
            ]
        ];

        foreach ($receivers as $receiver)
        {
            $this->input['receivers'] = $receiver;

            $this->makeRequestAndCatchException(function()
            {
                $this->createVirtualAccount($this->input);
            },
            Exception\BadRequestException::class);

            $va = $this->getDbLastEntity('virtual_account');

            // No VA is created
            $this->assertNull($va);
        }
    }

    public function testCreateWithOrder()
    {
        $this->input['order_id'] = $this->createOrder([])['id'];

        $this->makeRequestAndCatchException(function()
        {
            $this->createVirtualAccount($this->input);
        },
        Exception\BadRequestValidationFailureException::class);

        $va = $this->getDbLastEntity('virtual_account');

        // No VA is created
        $this->assertNull($va);
    }

    public function testCreateOnNonMindgateTerminal()
    {
        $this->terminal->delete();

        $this->fixtures->create('terminal:shared_upi_axis_intent_terminal');

        $this->makeRequestAndCatchException(
            function()
            {
                $this->createVirtualAccount($this->input);
            },
            Exception\LogicException::class,
            TraceCode::QR_CODE_UPI_QR_TERMINAL_NOT_FOUND_FOR_MERCHANT);
    }

    public function testGatewayFailureOnCreate()
    {
        $this->input['description'] = 'Gateway Failure';

        $this->makeRequestAndCatchException(function()
        {
            $this->createVirtualAccount($this->input);
        },
        Exception\GatewayErrorException::class);

        $this->va = $this->getDbLastEntity('virtual_account');

        // No VA is created
        $this->assertNull($this->va);
    }

    public function testSuccessCallback()
    {
        $response = $this->createVirtualAccount($this->input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $this->mockServerContentFunction(
            function(& $content, string $action)
            {
                if ($action === 'callback')
                {
                    $content[9] = '910000123456';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => $this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'random@upi',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Payment\Entity::MERCHANT_ID         => Merchant\Account::TEST_ACCOUNT,
                Payment\Entity::AMOUNT              => 12388,
                Payment\Entity::METHOD              => Payment\Method::UPI,
                Payment\Entity::STATUS              => Payment\Status::CAPTURED,
                Payment\Entity::RECEIVER_ID         => $this->va->qrCode->getId(),
                Payment\Entity::RECEIVER_TYPE       => Receiver::QR_CODE,
                Payment\Entity::AMOUNT_AUTHORIZED   => 12388,
                Payment\Entity::VPA                 => 'random@upi',
                Payment\Entity::CUSTOMER_ID         => '100000customer',
                Payment\Entity::EMAIL               => 'test@razorpay.com',
                Payment\Entity::CONTACT             => '+911234567890',
                Payment\Entity::GATEWAY             => Payment\Gateway::UPI_MINDGATE,
                Payment\Entity::TERMINAL_ID         => $this->terminal->getId(),
                Payment\Entity::GATEWAY_CAPTURED    => true,
                Payment\Entity::LATE_AUTHORIZED     => false,
            ],
            $payment->toArray());

        $upi = $this->getDbLastEntity(Payment\Method::UPI);

        $this->assertArraySubset(
            [
                UpiEntity::GATEWAY                  => Payment\Gateway::UPI_MINDGATE,
                UpiEntity::PAYMENT_ID               => $payment->getId(),
                UpiEntity::ACTION                   => Payment\Action::AUTHORIZE,
                UpiEntity::TYPE                     => 'pay',
                UpiEntity::AMOUNT                   => 12388,
                UpiEntity::ACQUIRER                 => 'hdfc',
                UpiEntity::BANK                     => 'NPCI',
                UpiEntity::PROVIDER                 => 'upi',
                UpiEntity::VPA                      => 'random@upi',
                UpiEntity::ACCOUNT_NUMBER           => '10000000000',
                UpiEntity::IFSC                     => 'PNBI1111111',
                UpiEntity::RECEIVED                 => 1,
                UpiEntity::MERCHANT_REFERENCE       => $this->va->qrCode->getId(),
                UpiEntity::NPCI_REFERENCE_ID        => '910000123456',
            ],
            $upi->toArray());

        $this->va->refresh();

        $this->assertArraySubset(
            [
                Entity::AMOUNT_EXPECTED => 12388,
                Entity::AMOUNT_RECEIVED => 12388,
                Entity::AMOUNT_PAID     => 12388,
            ],
            $this->va->toArray());

        $this->runQrPaymentRequestAssertions(true, true,'910000123456', null, $upi->getId());
    }

    protected function runQrPaymentRequestAssertions(bool $isCreated, $expected, string $transactionReference, $errorMessage = null,
                                                     $upiId = null)
    {
        $qrPaymentRequest = $this->getDbLastEntity('qr_payment_request');

        $this->assertNotNull($qrPaymentRequest['request_payload']);
        $this->assertNull($qrPaymentRequest['bharat_qr_id']);
        $this->assertEquals($isCreated, $qrPaymentRequest['is_created']);
        $this->assertEquals($expected, $qrPaymentRequest['expected']);
        $this->assertEquals($upiId, $qrPaymentRequest['upi_id']);
        if ($errorMessage !== null)
        {
            $this->assertNotNull($qrPaymentRequest['failure_reason']);
        }
        $this->assertEquals($transactionReference, $qrPaymentRequest['transaction_reference']);
    }

    public function testFailureCallback()
    {
        $response = $this->createVirtualAccount($this->input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $this->mockServerContentFunction(
            function(& $content, string $action) use (& $asserted)
            {
                if ($action === 'callback')
                {
                    $this->assertSame('ZA', $content[6]);
                    $this->assertSame('F', $content[4]);

                    $asserted = true;
                }

                if ($action === 'verify')
                {
                    $content['status']      = 'FAILURE';
                    $content['resp_code']   = 'ZA';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => $this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'failed@hdfcbank',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($asserted);

        $this->assertTrue($response['success']);

        $payment = $this->getLastEntity('payment');

        $this->assertNull($payment);

        $this->va->refresh();

        $this->assertArraySubset(
            [
                Entity::AMOUNT_EXPECTED => 12388,
                Entity::AMOUNT_RECEIVED => 0,
                Entity::AMOUNT_PAID     => 0,
            ],
            $this->va->toArray());

        $this->runQrPaymentRequestAssertions(false, true, '910000123456', 'Gateway Error', null);
    }

    public function testPendingCallback()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        // For VPA type receiver as the shared sharp terminal is not seeded
        $this->fixtures->create('terminal:vpa_shared_terminal_icici');
        $this->fixtures->create('terminal:shared_bank_account_terminal');

        $response = $this->createVirtualAccount($this->input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $this->mockServerContentFunction(
            function(& $content, string $action)
            {
                if ($action === 'callback')
                {
                    $content[4] = 'PENDING';
                    $content[6] = 'RB';
                }
                if ($action === 'verify')
                {
                    $content['status']      = 'PENDING';
                    $content['resp_code']   = 'RB';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => $this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'random@upi',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $qrCode = $this->getDbLastEntity('qr_code');
        $this->assertNull($qrCode->getAmount());

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Payment\Entity::MERCHANT_ID         => Merchant\Account::TEST_ACCOUNT,
                Payment\Entity::AMOUNT              => 12388,
                Payment\Entity::METHOD              => Payment\Method::UPI,
                Payment\Entity::STATUS              => Payment\Status::FAILED,
                Payment\Entity::RECEIVER_ID         => $qrCode->getId(),
                Payment\Entity::RECEIVER_TYPE       => Receiver::QR_CODE,
                Payment\Entity::AMOUNT_AUTHORIZED   => 0,
                Payment\Entity::VPA                 => 'random@upi',
                Payment\Entity::EMAIL               => 'void@razorpay.com',
                Payment\Entity::CONTACT             => '+919999999999',
                Payment\Entity::GATEWAY             => Payment\Gateway::UPI_MINDGATE,
                Payment\Entity::TERMINAL_ID         => $this->terminal->getId(),
                Payment\Entity::GATEWAY_CAPTURED    => false,
                Payment\Entity::LATE_AUTHORIZED     => false,
            ],
            $payment->toArray());

        $this->va->refresh();

        $this->assertArraySubset(
            [
                Entity::AMOUNT_EXPECTED => 12388,
                Entity::AMOUNT_RECEIVED => 0,
                Entity::AMOUNT_PAID     => 0,
            ],
            $this->va->toArray());

        $this->mockServerContentFunction(
            function(& $content, string $action)
            {
                if ($action === 'verify')
                {
                    $this->assertSame('00', $content['resp_code']);
                }
            });

        $response = $this->authorizedFailedPayment($payment->getPublicId());

        $this->va->refresh();

        $upi = $this->getDbLastEntity('upi');

        $this->runQrPaymentRequestAssertions(true, false, '910000123456', null, $upi->getId());
    }

    public function testAmountMismatchOnCallback()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        // For VPA type receiver as the shared sharp terminal is not seeded
        $this->fixtures->create('terminal:vpa_shared_terminal_icici');
        $this->fixtures->create('terminal:shared_bank_account_terminal');

        $response = $this->createVirtualAccount($this->input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => $this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'random@upi',
                'amount'                => ($this->va->getAmountExpected() - 100),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $qrCode = $this->getDbLastEntity('qr_code');
        $this->assertNull($qrCode->getAmount());

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Payment\Entity::MERCHANT_ID         => Merchant\Account::TEST_ACCOUNT,
                Payment\Entity::AMOUNT              => 12288,
                Payment\Entity::METHOD              => Payment\Method::UPI,
                Payment\Entity::STATUS              => Payment\Status::AUTHORIZED,
                Payment\Entity::RECEIVER_ID         => $qrCode->getId(),
                Payment\Entity::RECEIVER_TYPE       => Receiver::QR_CODE,
                Payment\Entity::AMOUNT_AUTHORIZED   => 12288,
                Payment\Entity::VPA                 => 'random@upi',
                Payment\Entity::EMAIL               => 'void@razorpay.com',
                Payment\Entity::CONTACT             => '+919999999999',
                Payment\Entity::GATEWAY             => Payment\Gateway::UPI_MINDGATE,
                Payment\Entity::TERMINAL_ID         => $this->terminal->getId(),
                Payment\Entity::GATEWAY_CAPTURED    => true,
                Payment\Entity::LATE_AUTHORIZED     => false,
            ],
            $payment->toArray());

        $upi = $this->getDbLastEntity(Payment\Method::UPI);

        $this->assertArraySubset(
            [
                UpiEntity::GATEWAY                  => Payment\Gateway::UPI_MINDGATE,
                UpiEntity::PAYMENT_ID               => $payment->getId(),
                UpiEntity::ACTION                   => Payment\Action::AUTHORIZE,
                UpiEntity::TYPE                     => 'pay',
                UpiEntity::AMOUNT                   => 12288,
                UpiEntity::ACQUIRER                 => 'hdfc',
                UpiEntity::BANK                     => 'NPCI',
                UpiEntity::PROVIDER                 => 'upi',
                UpiEntity::VPA                      => 'random@upi',
                UpiEntity::ACCOUNT_NUMBER           => '10000000000',
                UpiEntity::IFSC                     => 'PNBI1111111',
                UpiEntity::RECEIVED                 => 1,
                UpiEntity::MERCHANT_REFERENCE       => $this->va->qrCode->getId(),
                UpiEntity::NPCI_REFERENCE_ID        => '910000123456',
            ],
            $upi->toArray());

        $this->va->refresh();

        $this->assertArraySubset(
            [
                Entity::AMOUNT_EXPECTED => 12388,
                Entity::AMOUNT_RECEIVED => 0,
                Entity::AMOUNT_PAID     => 0,
            ],
            $this->va->toArray());

        $this->assertArraySubset(
            [
                Entity::ID              => 'ShrdVirtualAcc',
                Entity::MERCHANT_ID     => Merchant\Account::TEST_ACCOUNT,
                Entity::AMOUNT_EXPECTED => null,
                Entity::AMOUNT_RECEIVED => 12288,
                Entity::AMOUNT_PAID     => 12288,
            ], $qrCode->source->toArray());


        $upi = $this->getDbLastEntity('upi');

        $this->runQrPaymentRequestAssertions(true, false,'910000123456', null, $upi->getId());
    }

    public function testCloseByOnCallback()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        // For VPA type receiver as the shared sharp terminal is not seeded
        $this->fixtures->create('terminal:vpa_shared_terminal_icici');
        $this->fixtures->create('terminal:shared_bank_account_terminal');

        $response = $this->createVirtualAccount($this->input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $this->va->setAttribute(Entity::CLOSE_BY, (time() - 1));
        $this->va->saveOrFail();

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => $this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'random@upi',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $qrCode = $this->getDbLastEntity('qr_code');
        $this->assertNull($qrCode->getAmount());

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Payment\Entity::MERCHANT_ID         => Merchant\Account::TEST_ACCOUNT,
                Payment\Entity::AMOUNT              => 12388,
                Payment\Entity::METHOD              => Payment\Method::UPI,
                Payment\Entity::STATUS              => Payment\Status::AUTHORIZED,
                Payment\Entity::RECEIVER_ID         => $qrCode->getId(),
                Payment\Entity::RECEIVER_TYPE       => Receiver::QR_CODE,
                Payment\Entity::AMOUNT_AUTHORIZED   => 12388,
                Payment\Entity::VPA                 => 'random@upi',
                Payment\Entity::EMAIL               => 'void@razorpay.com',
                Payment\Entity::CONTACT             => '+919999999999',
                Payment\Entity::GATEWAY             => Payment\Gateway::UPI_MINDGATE,
                Payment\Entity::TERMINAL_ID         => $this->terminal->getId(),
                Payment\Entity::GATEWAY_CAPTURED    => true,
                Payment\Entity::LATE_AUTHORIZED     => false,
            ],
            $payment->toArray());

        $upi = $this->getDbLastEntity(Payment\Method::UPI);

        $this->assertArraySubset(
            [
                UpiEntity::GATEWAY                  => Payment\Gateway::UPI_MINDGATE,
                UpiEntity::PAYMENT_ID               => $payment->getId(),
                UpiEntity::ACTION                   => Payment\Action::AUTHORIZE,
                UpiEntity::TYPE                     => 'pay',
                UpiEntity::AMOUNT                   => 12388,
                UpiEntity::ACQUIRER                 => 'hdfc',
                UpiEntity::BANK                     => 'NPCI',
                UpiEntity::PROVIDER                 => 'upi',
                UpiEntity::VPA                      => 'random@upi',
                UpiEntity::ACCOUNT_NUMBER           => '10000000000',
                UpiEntity::IFSC                     => 'PNBI1111111',
                UpiEntity::RECEIVED                 => 1,
                UpiEntity::MERCHANT_REFERENCE       => $this->va->qrCode->getId(),
                UpiEntity::NPCI_REFERENCE_ID        => '910000123456',
            ],
            $upi->toArray());

        $this->va->refresh();

        $this->assertArraySubset(
            [
                Entity::AMOUNT_EXPECTED => 12388,
                Entity::AMOUNT_RECEIVED => 0,
                Entity::AMOUNT_PAID     => 0,
            ],
            $this->va->toArray());

        $this->assertArraySubset(
            [
                Entity::ID              => 'ShrdVirtualAcc',
                Entity::MERCHANT_ID     => Merchant\Account::TEST_ACCOUNT,
                Entity::AMOUNT_EXPECTED => null,
                Entity::AMOUNT_RECEIVED => 12388,
                Entity::AMOUNT_PAID     => 12388,
            ], $qrCode->source->toArray());

        $upi = $this->getDbLastEntity('upi');

        $this->runQrPaymentRequestAssertions(true, false,'910000123456', null, $upi->getId());
    }

    public function testClosedOnCallback()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        // For VPA type receiver as the shared sharp terminal is not seeded
        $this->fixtures->create('terminal:vpa_shared_terminal_icici');
        $this->fixtures->create('terminal:shared_bank_account_terminal');

        $response = $this->createVirtualAccount($this->input);

        $this->va = $this->getDbLastEntity('virtual_account');

        // Here we are marking VA closed
        $this->va->setStatus('closed');
        $this->va->setClosedAt(time() - 1);

        $this->va->saveOrFail();

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => $this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'random@upi',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $qrCode = $this->getDbLastEntity('qr_code');
        $this->assertNull($qrCode->getAmount());

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Payment\Entity::MERCHANT_ID         => Merchant\Account::TEST_ACCOUNT,
                Payment\Entity::AMOUNT              => 12388,
                Payment\Entity::METHOD              => Payment\Method::UPI,
                Payment\Entity::STATUS              => Payment\Status::AUTHORIZED,
                Payment\Entity::RECEIVER_ID         => $qrCode->getId(),
                Payment\Entity::RECEIVER_TYPE       => Receiver::QR_CODE,
                Payment\Entity::AMOUNT_AUTHORIZED   => 12388,
                Payment\Entity::VPA                 => 'random@upi',
                Payment\Entity::EMAIL               => 'void@razorpay.com',
                Payment\Entity::CONTACT             => '+919999999999',
                Payment\Entity::GATEWAY             => Payment\Gateway::UPI_MINDGATE,
                Payment\Entity::TERMINAL_ID         => $this->terminal->getId(),
                Payment\Entity::GATEWAY_CAPTURED    => true,
                Payment\Entity::LATE_AUTHORIZED     => false,
            ],
            $payment->toArray());

        $upi = $this->getDbLastEntity(Payment\Method::UPI);

        $this->assertArraySubset(
            [
                UpiEntity::GATEWAY                  => Payment\Gateway::UPI_MINDGATE,
                UpiEntity::PAYMENT_ID               => $payment->getId(),
                UpiEntity::ACTION                   => Payment\Action::AUTHORIZE,
                UpiEntity::TYPE                     => 'pay',
                UpiEntity::AMOUNT                   => 12388,
                UpiEntity::ACQUIRER                 => 'hdfc',
                UpiEntity::BANK                     => 'NPCI',
                UpiEntity::PROVIDER                 => 'upi',
                UpiEntity::VPA                      => 'random@upi',
                UpiEntity::ACCOUNT_NUMBER           => '10000000000',
                UpiEntity::IFSC                     => 'PNBI1111111',
                UpiEntity::RECEIVED                 => 1,
                UpiEntity::MERCHANT_REFERENCE       => $this->va->qrCode->getId(),
                UpiEntity::NPCI_REFERENCE_ID        => '910000123456',
            ],
            $upi->toArray());

        $this->va->refresh();

        $this->assertArraySubset(
            [
                Entity::AMOUNT_EXPECTED => 12388,
                Entity::AMOUNT_RECEIVED => 0,
                Entity::AMOUNT_PAID     => 0,
            ],
            $this->va->toArray());

        $this->assertArraySubset(
            [
                Entity::ID              => 'ShrdVirtualAcc',
                Entity::MERCHANT_ID     => Merchant\Account::TEST_ACCOUNT,
                Entity::AMOUNT_EXPECTED => null,
                Entity::AMOUNT_RECEIVED => 12388,
                Entity::AMOUNT_PAID     => 12388,
            ], $qrCode->source->toArray());

        $upi = $this->getDbLastEntity('upi');

        $this->runQrPaymentRequestAssertions(true, false, '910000123456', null, $upi->getId());
    }

    public function testDuplicateOnCallback()
    {
        // This will create the VA and have all required assertion in place
        $this->testSuccessCallback();

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => $this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'random@upi',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        // No new payment or upi entry is created
        $this->assertSame(1, $this->getDbEntities('payment')->count());
        $this->assertSame(1, $this->getDbEntities('upi')->count());
    }

    public function testVerifyFailureOnCallback()
    {
        $response = $this->createVirtualAccount($this->input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $this->mockServerContentFunction(
            function(& $content, string $action)
            {
                if ($action === 'verify')
                {
                    $content['status']      = 'FAILURE';
                    $content['resp_code']   = 'ZA';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => $this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'failed@hdfcbank',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $payment = $this->getLastEntity('payment');

        $this->assertNull($payment);

        $this->va->refresh();

        $this->assertArraySubset(
            [
                Entity::AMOUNT_EXPECTED => 12388,
                Entity::AMOUNT_RECEIVED => 0,
                Entity::AMOUNT_PAID     => 0,
            ],
            $this->va->toArray());
    }

    public function createTestOrg()
    {
        $this->ba->adminAuth();

        $org = $this->fixtures->create('org', [
            'display_name'            => 'HDFC CollectNow',
            'business_name'            => 'HDFC Bank',
        ]);

        $this->fixtures->create('feature', [
            'name'          => 'org_custom_upi_logo',
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id' => $org->getId(),
            'hostname' => 'hdfcupicollect.razorpay.com',
        ]);

        return $org;
    }


    public function testCallbackSuccessUpiQrHdfc()
    {
        $this->fixtures->merchant->setCategory('1111');

        $this->fixtures->merchant->addFeatures(['upiqr_v1_hdfc']);
        
        $org = $this->createTestOrg();

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Test Name',
            'org_id' => $org->getId(),
        ]);

        $terminal = $this->fixtures->create('terminal', [
            'id'                        => '10000000000112',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'razorpay upi mindgate',
            'gateway_terminal_id'       => 'nodal account upi hdfc',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            // Sample hex for aes encryption, not in used
            'gateway_terminal_password' => '93158d5892188161a259db660ddb1d0b',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
            'vpa'                       => 'unittest@hdfcbank',
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ]);

        $input = [
            "usage"=> "multiple_use",
            "description"=> "QR Description",
            'amount_expected' => 1 ,
            "name"=> "TestName",
            "notes"=> [
                "test"=> "Notes",
                "test2"=> "Notes2"
            ],
            "receivers"=> [
                "types"=> [
                    "qr_code"
                ],
                "qr_code"=> [
                    "method"=> [
                        "card"=> false,
                        "upi"=> true,
                    ]
                ]
            ]
        ];

        $response = $this->createVirtualAccount($input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                // Any random string, why not va id
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => 'STQ'.$this->va->qrCode->getId(),
            ],
            [
                // This is customer VPA
                'vpa'                   => 'random@upi',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Payment\Entity::MERCHANT_ID         => Merchant\Account::TEST_ACCOUNT,
                Payment\Entity::AMOUNT              => 1,
                Payment\Entity::METHOD              => Payment\Method::UPI,
                Payment\Entity::STATUS              => Payment\Status::CAPTURED,
                Payment\Entity::RECEIVER_ID         => $this->va->qrCode->getId(),
                Payment\Entity::RECEIVER_TYPE       => Receiver::QR_CODE,
                Payment\Entity::AMOUNT_AUTHORIZED   => 1,
                Payment\Entity::VPA                 => 'random@upi',
                Payment\Entity::EMAIL               => 'void@razorpay.com',
                Payment\Entity::CONTACT             => '+919999999999',
                Payment\Entity::GATEWAY             => Payment\Gateway::UPI_MINDGATE,
                Payment\Entity::TERMINAL_ID         => $terminal['id'],
                Payment\Entity::GATEWAY_CAPTURED    => true,
                Payment\Entity::LATE_AUTHORIZED     => false,
            ],
            $payment->toArray());

        $upi = $this->getDbLastEntity(Payment\Method::UPI);

        $this->assertArraySubset(
            [
                UpiEntity::GATEWAY                  => Payment\Gateway::UPI_MINDGATE,
                UpiEntity::PAYMENT_ID               => $payment->getId(),
                UpiEntity::ACTION                   => Payment\Action::AUTHORIZE,
                UpiEntity::TYPE                     => 'pay',
                UpiEntity::AMOUNT                   => 1,
                UpiEntity::ACQUIRER                 => 'hdfc',
                UpiEntity::BANK                     => 'NPCI',
                UpiEntity::PROVIDER                 => 'upi',
                UpiEntity::VPA                      => 'random@upi',
                UpiEntity::ACCOUNT_NUMBER           => '10000000000',
                UpiEntity::IFSC                     => 'PNBI1111111',
                UpiEntity::RECEIVED                 => 1,
                UpiEntity::MERCHANT_REFERENCE       => 'STQ'.$this->va->qrCode->getId(),
                UpiEntity::NPCI_REFERENCE_ID        => '910000123456',
            ],
            $upi->toArray());

        $this->va->refresh();

        $this->assertArraySubset(
            [
                Entity::AMOUNT_EXPECTED => 1,
                Entity::AMOUNT_RECEIVED => 1,
                Entity::AMOUNT_PAID     => 1,
            ],
            $this->va->toArray());

        $this->runQrPaymentRequestAssertions(true, true,'910000123456', null, $upi->getId());

        $this->assertQrString($this->va->qrCode->qr_string, $this->va,'multiple_use');

        $qrPayment = $this->getDbLastEntity('qr_payment');

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCode['id'], $qrPayment['qr_code_id']);

        $this->assertEquals(1, $qrPayment['expected']);

        $this->assertEquals('910000123456', $payment['acquirer_data']['rrn']);
        $this->assertEquals('910000123456', $payment['reference16']);

    }

    //*************************** Helpers ******************************//

    protected function assertQrString(string $string, Entity $va,string $usageType = 'single_use')
    {
        parse_str(str_replace('upi://pay?', '', $string), $params);

        $this->assertSame($this->terminal->getGatewayMerchantId2(), $params['pa']);
        $this->assertSame('TestMerchant', $params['pn']);
        if($usageType === 'multiple_use')
            $this->assertSame('STQ'.$va->qrCode->getId(), $params['tr']);
        else
            $this->assertSame($va->qrCode->getId(), $params['tr']);
        $this->assertSame('TestMerchant' . str_replace(' ', '', $va->description), $params['tn']);
        $this->assertSame(amount_format_IN($va->getAmountExpected()), $params['am']);
        $this->assertSame('INR', $params['cu']);
        $this->assertSame('1111', $params['mc']);
    }
}
