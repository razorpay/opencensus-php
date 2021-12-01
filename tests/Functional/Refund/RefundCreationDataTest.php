<?php

namespace RZP\Tests\Functional\Refund;

use RZP\Services\Scrooge;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Refund\Helpers as RefundHelpers;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class RefundCreationDataTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RefundCreationDataTestData.php';

        parent::setUp();
    }

    // Default case
    public function testFetchRefundCreationDataDefaultCase()
    {
        // when refund create fails with errors trivial to dashboard
        // SERVER_ERROR_PRICING_RULE_ABSENT in this case
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 100,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_DISABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => null,
                    RefundEntity::TAX => null,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => []
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Instant refunds enabled
    public function testFetchRefundCreationDataIrEnabled()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 100,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_ENABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => 943,
                    RefundEntity::TAX => 144,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => []
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Instant refunds disabled, no coverage
    public function testFetchRefundCreationDataIrDisabledNoCoverage()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 100,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn([
                               'mode' => 'IMPS',
                               'gateway_refund_support' => true,
                               'instant_refund_support' => false,
                               'payment_age_limit_for_gateway_refund' => null
                           ]);

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_DISABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => null,
                    RefundEntity::TAX => null,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => [
                RefundConstants::MESSAGE_KEY_IR_SUPPORTED_INSTRUMENTS => [
                    RefundConstants::MESSAGE_REASON => RefundConstants::MESSAGE_REASON_IR_SUPPORTED_INSTRUMENTS
                ]
            ]
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Instant refunds default optimum
    public function testFetchRefundCreationDataIrDefaultOptimum()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->merchant->edit('10000000000000', ['default_refund_speed' => 'optimum']);

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 100,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn([
                               'mode' => 'IMPS',
                               'gateway_refund_support' => true,
                               'instant_refund_support' => true,
                               'payment_age_limit_for_gateway_refund' => null
                           ]);

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_DEFAULT_OPTIMUM,
                RefundConstants::FEES => [
                    RefundEntity::FEE => 943,
                    RefundEntity::TAX => 144,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => []
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Only Instant refunds on aged payments
    public function testFetchRefundCreationDataIrOnlyOptimum()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 100,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn([
                               'mode' => 'IMPS',
                               'gateway_refund_support' => false,
                               'instant_refund_support' => true,
                               'payment_age_limit_for_gateway_refund' => 180
                           ]);

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_ONLY_OPTIMUM,
                RefundConstants::FEES => [
                    RefundEntity::FEE => 943,
                    RefundEntity::TAX => 144,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => [
                RefundConstants::MESSAGE_KEY_REFUNDS_ON_AGED_PAYMENTS => [
                    RefundConstants::MESSAGE_REASON => RefundHelpers::getBlockRefundsMessage(1)
                ]
            ]
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Refund not allowed because of payments age
    public function testFetchRefundCreationDataRefundNotAllowed()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 100,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn([
                               'mode' => null,
                               'gateway_refund_support' => false,
                               'instant_refund_support' => false,
                               'payment_age_limit_for_gateway_refund' => 180
                           ]);

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_DISABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => null,
                    RefundEntity::TAX => null,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => false,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => [
                RefundConstants::MESSAGE_KEY_REFUNDS_ON_AGED_PAYMENTS => [
                    RefundConstants::MESSAGE_REASON => RefundHelpers::getBlockRefundsMessage()
                ]
            ]
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Not enough balance for instant refund
    public function testFetchRefundCreationDataIrDisabledLowBalance()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->balance->edit('10000000000000', ['balance' => $payment['amount']]);

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => $payment['amount'],
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_DISABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => null,
                    RefundEntity::TAX => null,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => [
                RefundConstants::MESSAGE_KEY_INSUFFICIENT_FUNDS => [
                    RefundConstants::MESSAGE_REASON => RefundConstants::MESSAGE_REASON_IR_INSUFFICIENT_FUNDS
                ]
            ]
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Not enough balance for refund
    public function testFetchRefundCreationDataRefundDisabledLowBalance()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->balance->edit('10000000000000', ['balance' => 0]);

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => $payment['amount'],
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_DISABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => null,
                    RefundEntity::TAX => null,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => false,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => [
                RefundConstants::MESSAGE_KEY_INSUFFICIENT_FUNDS => [
                    RefundConstants::MESSAGE_REASON => RefundConstants::MESSAGE_REASON_INSUFFICIENT_FUNDS
                ]
            ]
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Not enough credits for refund
    public function testFetchRefundCreationDataRefundDisabledLowCredits()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => $payment['amount'],
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_DISABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => null,
                    RefundEntity::TAX => null,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => false,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => [
                RefundConstants::MESSAGE_KEY_INSUFFICIENT_FUNDS => [
                    RefundConstants::MESSAGE_REASON => RefundConstants::MESSAGE_REASON_INSUFFICIENT_FUNDS
                ]
            ]
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Transfer reversal option for single transfer
    public function testFetchRefundCreationDataSingleTransferReversalPartialRefund()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $transfers[0] = [
            'account' => 'acc_' . $account['id'],
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 100,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_ENABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => 943,
                    RefundEntity::TAX => 144,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => true,
            RefundConstants::MESSAGES => []
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Transfer reversal option for single transfer
    public function testFetchRefundCreationDataSingleTransferReversalFullRefund()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $transfers[0] = [
            'account' => 'acc_' . $account['id'],
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => $payment['amount'],
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_ENABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => 943,
                    RefundEntity::TAX => 144,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => true,
            RefundConstants::MESSAGES => []
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Transfer reversal option for multiple transfers full refund
    public function testFetchRefundCreationDataMultipleTransfersReversalFullRefund()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $transfers[0] = [
            'account' => 'acc_' . $account['id'],
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers[1] = [
            'account' => 'acc_' . $account['id'],
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => $payment['amount'],
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_ENABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => 943,
                    RefundEntity::TAX => 144,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => true,
            RefundConstants::MESSAGES => []
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // Transfer reversal option for multiple transfers partial refund
    public function testFetchRefundCreationDataMultipleTransfersReversalPartialRefund()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $transfers[0] = [
            'account' => 'acc_' . $account['id'],
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers[1] = [
            'account' => 'acc_' . $account['id'],
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 200,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);

        $expectedOutput = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => 'INR'
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_ENABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => 943,
                    RefundEntity::TAX => 144,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => []
        ];

        $this->assertEquals($expectedOutput, $response);
    }

    // A payment should be accessible only from its merchant auth or a linked account
    public function testFetchRefundCreationDataMerchantAccess()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $this->fixtures->edit('payment', $payment['id'], ['merchant_id' => '10000000000001']);

        // proxy auth
        $this->ba->proxyAuth();

        $input = [
            'payment_id' => $payment['id'],
            'amount' => 100,
        ];

        $this->testData['fetchRefundCreationData']['request']['content'] = $input;

        $exception = false;

        try
        {
            $this->runRequestResponseFlow($this->testData['fetchRefundCreationData']);
        }
        catch(\Exception $e)
        {
            $this->assertEquals('BAD_REQUEST_INVALID_ID', $e->getCode());

            $exception = true;
        }

        $this->assertTrue($exception);
    }
}
