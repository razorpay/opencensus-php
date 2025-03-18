<?php

namespace RZP\Tests\Functional\International;

use mysql_xdevapi\Exception;
use Queue;
use Mockery;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Factory;
use RZP\Error\ErrorCode;
use RZP\Services\CrossBorderImportServiceClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\InternationalIntegration;
use RZP\Tests\Traits\MocksSplitz;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;

class CBIPaymentCreateTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use MocksSplitz;

    protected $testData = null;
    protected $payment = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ba->publicAuth();
    }


    public function testValidateImportFlowDataIfApplicableSuccess()
    {
        $this->setMockForCBIClient(200);
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];


          $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        $paymentEntity = $this->getDbLastPayment();
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
    }

    public function testValidateImportFlowDataIfApplicableBadRequestError()
    {
        $this->setMockForCBIClient(400);
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'USD',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'USD';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $this->doS2SPrivateAuthJsonPayment($payment);
        },
            \RZP\Exception\BadRequestException::class,
            'Currency is not supported');
    }

    public function testValidateImportFlowDataIfApplicableServerError()
    {
        $this->setMockForCBIClient(500);
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function () use ($payment) {
            $this->doS2SPrivateAuthJsonPayment($payment);
        },
            \RZP\Exception\ServerErrorException::class,
            'SERVER_ERROR'
        );
    }

    public function testValidateJPMCImportFlowDataInCrossBorderImportServicePrimarySuccess()
    {
        $this->setMockForCBIClient(200);
        $this->mockSplitzEvaluation(0);
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];


        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        $paymentEntity = $this->getDbLastPayment();
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
    }

    public function testValidateJPMCImportFlowDataInCrossBorderImportServiceShadowSuccess()
    {
        $this->setMockForCBIClient(200);
        $this->mockSplitzEvaluation(1);
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];


        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        $paymentEntity = $this->getDbLastPayment();
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
    }

    public function setMockForCBIClient($statusCode)
    {
        $mockResponseValidateImportPayment = [];

        $ex = null;

        switch ($statusCode) {
            case 200:
                $mockResponseValidateImportPayment = [
                    "success" => true,
                ];
                break;
            case 400:
                $ex = (new BadRequestHttpException(
                    'Currency is not supported',
                    null,
                ));
                break;
            case 500:
                $ex = (new \RZP\Exception\ServerErrorException(
                    'SERVER_ERROR',
                    'SERVER_ERROR',
                    [
                        'status_code'   => 500,
                        'error' => [
                            'code' => 'SERVER_ERROR',
                            'description' => 'Failed to complete request'
                        ]
                    ]
                ));
                break;
        }

        $cbiServiceMock = $this->getMockBuilder(CrossBorderImportServiceClient::class)
            ->onlyMethods(['validateImportPayment'])->getMock();

        $this->app->instance('cross_border_import_service', $cbiServiceMock);

       if($statusCode === 200) {
           $cbiServiceMock->method("validateImportPayment")
               ->willReturn($mockResponseValidateImportPayment);
       }
       else{
           $cbiServiceMock->method("validateImportPayment")
               ->willThrowException($ex);
       }
    }

    protected function getOrderMetaValue()
    {
        $app = App::getFacadeRoot();
        $shipping_address = [
            'line1'         => 'line_one',
            'line2'         => 'line_two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'IND',
            'type'          => 'shipping_address',
            'primary'       => true
        ];

        $customer = [
            'name'              => 'Test Customer',
            'method'            => 'sameday',
            'gift_wrap'         => false,
            'shipping_address'  => $shipping_address,
        ];

        return [
            'campaign'          => null,
            'customer'          => null,
            'refund_allowed'    => null,
            'line_items'        => null,
            'line_items_total'  => null,
            'customer_details'  => $customer,
        ];
    }

    private function mockSplitzEvaluation($experiment)
    {
        if($experiment){
            //shadow
            $input = [
                "experiment_id" => "PbW2rUOyxKxiik",
                "id"            => "10000000000000",
                'request_data'  => json_encode(
                    [
                        'merchant_id' => "10000000000000",
                    ]),
            ];

            $output = [
                "response" => [
                    "variant" => [
                        "name" => 'variant_on',
                    ]
                ]
            ];

            $this->mockSplitzTreatment($input, $output);
        }
        else{
            //primary
            $input = [
                "experiment_id" => "PYD8kKkFiicXwe",
                "id"            => "10000000000000",
                'request_data'  => json_encode(
                    [
                        'merchant_id' => "10000000000000",
                    ]),
            ];

            $output = [
                "response" => [
                    "variant" => [
                        "name" => 'variant_on',
                    ]
                ]
            ];

            $this->mockSplitzTreatment($input, $output);
        }

    }

    public function testPayoutStatusPushForICATransferAsSource()
    {
        $cbiServiceMock =  Mockery::mock(CrossBorderImportServiceClient::class);

        $cbiServiceMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('cross_border_import_service', $cbiServiceMock);

        $payout = $this->fixtures->create('payout', [
            'status'            =>      'processed',
            'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout->getId(),
            'source_id' => '1nvp2XPMmaRLxb',
            'source_type' => 'ica_transfer',
            'priority' => 1
        ]);

        SourceUpdater::update($payout);

        // assert that the Payout Update Status was called when feature was enabled
        $cbiServiceMock->shouldHaveReceived('pushPayoutStatusUpdate');
    }
}
