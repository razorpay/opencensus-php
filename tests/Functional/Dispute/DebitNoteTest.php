<?php

namespace Functional\Dispute;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;

class DebitNoteTest extends TestCase
{
    use FreshdeskTrait;
    use DisputeTrait;
    use DbEntityFetchTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DebitNoteTestData.php';

        parent::setUp();

        $this->ba->batchAppAuth();

        $this->setUpFreshdeskClientMock();

        $this->testData[$this->getName()]['request']['headers']['x-creator-id'] = 'RzrpySprAdmnId';

        $this->fixtures->edit('merchant', '10000000000000', [
            'name' => 'test merchant name',
        ]);


        $this->fixtures->create('merchant_detail', [
            'merchant_id'    => '10000000000000',
            'contact_mobile' => '9876543210',
        ]);

        $this->mockStork();

        $this->app['config']->set('services.disputes.mock', true);
    }

    //todo: move to trait
    protected function mockStork()
    {
        $this->storkMock = Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);
    }

    protected function expectStorkWhatsappRequest($expectedTemplate, $expectedInput,
                                                  $expectedReceiver = '+919876543210'): void
    {
        $this->storkMock
            ->shouldReceive('sendWhatsappMessage')
            ->times(1)
            ->with(
                Mockery::on(function ($mode)
                {
                    return true;
                }),
                Mockery::on(function ($template) use ($expectedTemplate)
                {
                    return $expectedTemplate === $template;
                }),
                Mockery::on(function ($receiver) use ($expectedReceiver)
                {
                    return $receiver === $expectedReceiver;
                }),
                Mockery::on(function ($input) use ($expectedInput)
                {
                    $this->assertArraySelectiveEquals($expectedInput, $input);

                    return true;
                })
            )
            ->andReturnUsing(function ()
            {
                $response = new \WpOrg\Requests\Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
    }

    protected function expectStorkSmsRequest($expectInput): void
    {
        $this->storkMock
            ->shouldReceive('sendSms')
            ->times(1)
            ->with(
                Mockery::on(function ($mode)
                {
                    return true;
                }),
                Mockery::on(function ($input) use ($expectInput)
                {
                    $this->assertArraySelectiveEquals($expectInput, $input);

                    return true;
                })
            )
            ->andReturnUsing(function ()
            {
                return ['key' => 'value'];
            });
    }

    public function testBatchCreate()
    {
        $this->expectFreshdeskCurlRequest(
            [
                'subject'         => 'Regarding your commercial debit note - 10000000000000',
                'status'          => 2,
                'tags'            => [
                    'bulk_debit_note',
                    'chargeback_debit_note',
                ],
                'group_id'        => 82000147810,
                'email_config_id' => 82000098669,
                'custom_fields'   => [
                    'cf_ticket_queue' => 'Merchant',
                    'cf_category'     => 'Chargebacks',
                    'cf_subcategory'  => 'Service Chargeback',
                    'cf_product'      => 'Payment Gateway',
                ],
            ]
        );

        $this->expectStorkWhatsappRequest(
            'Hi {merchant_name}, we wish to notify you regarding pending recoveries on your Razorpay Account. We request you to transfer the due amount to our Nodal account. Please check registered email for more details.',
            [
                'params' => [
                    'merchant_id'   => '10000000000000',
                    'merchant_name' => 'test merchant name',
                ],
            ]);

        $this->expectStorkSmsRequest([
            'templateName'      => 'sms.risk.debit_note_email_signup',
            'templateNamespace' => 'payments_risk',

        ]);


        $this->setUpFixtures(
            [
                'id'              => 'randomDisId123',
                'payment_id'      => 'randomPayId123',
                'internal_status' => 'lost_merchant_not_debited',
                'status'          => 'lost',
                'amount'          => 100,
            ],
            'payment:captured',
            [
                'id'          => 'randomPayId123',
                'base_amount' => 200,

            ]);

        $this->setUpFixtures(
            [
                'id'              => 'randomDisId456',
                'payment_id'      => 'randomPayId456',
                'internal_status' => 'lost_merchant_not_debited',
                'status'          => 'lost',
            ],
            'payment:captured',
            [
                'id'          => 'randomPayId456',
                'base_amount' => 100,

            ]);

        $this->fixtures->edit('balance', '10000000000000', [
            'balance' => 10,
        ]);

        $this->startTest();

        $debitNote = $this->getLastEntity('debit_note', true);

        $this->assertArraySelectiveEquals([
            'admin_id'    => 'RzrpySprAdmnId',
            'base_amount' => 300,
        ], $debitNote);

        $disputeDetails = $this->getDbEntities('debit_note_detail',
            [
                'detail_type' => 'dispute',
            ])->toArray();

        $this->assertArraySelectiveEquals([
            [
                'detail_type' => 'dispute',
                'detail_id'   => 'randomDisId123',
            ],
            [
                'detail_type' => 'dispute',
                'detail_id'   => 'randomDisId456',
            ],
        ], $disputeDetails);
    }

    protected function expectFreshdeskCurlRequest($expectedContent)
    {
        $this->freshdeskClientMock
            ->shouldReceive('makeCurlRequest')
            ->with(Mockery::on(function ($request) use ($expectedContent)
            {
                $this->assertArraySelectiveEquals($expectedContent, $request['content']);

                return true;
            }))
            ->zeroOrMoreTimes(1)
            ->andReturnUsing(function ()
            {
                return [
                    json_encode([
                        'id' => '99',
                    ]), 200,
                ];
            });
    }

    public function testBatchCreateMobileSignup()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'signup_via_email' => 0,
            'email'            => null,
        ]);


        $this->expectFreshdeskCurlRequest(
            [
                'subject'  => 'Regarding your commercial debit note - 10000000000000',
                'status'   => 2,
                'tags'     => [
                    'bulk_debit_note',
                    'chargeback_debit_note',
                ],
                'group_id' => 82000147810,
            ]
        );

        $this->expectStorkWhatsappRequest(
            'Hi {merchant_name}, we wish to notify you regarding pending recoveries on your Razorpay Account. We request you to transfer the due amount to our Nodal account. Please check link {supportTicketLink} for more details.',
            [
                'params' => [
                    'merchant_id'   => '10000000000000',
                    'merchant_name' => 'test merchant name',
                ],
            ]);

        $this->expectStorkSmsRequest([
            'templateName'      => 'sms.risk.debit_note_mobile_signup',
            'templateNamespace' => 'payments_risk',
            'contentParams'     => [
                'merchant_id'   => '10000000000000',
                'merchant_name' => 'test merchant name',
            ]
        ]);


        $this->setUpFixtures(
            [
                'id'              => 'randomDisId123',
                'payment_id'      => 'randomPayId123',
                'internal_status' => 'lost_merchant_not_debited',
                'status'          => 'lost',
                'amount'          => 100,
            ],
            'payment:captured',
            [
                'id'          => 'randomPayId123',
                'base_amount' => 200,

            ]);

        $this->setUpFixtures(
            [
                'id'              => 'randomDisId456',
                'payment_id'      => 'randomPayId456',
                'internal_status' => 'lost_merchant_not_debited',
                'status'          => 'lost',
            ],
            'payment:captured',
            [
                'id'          => 'randomPayId456',
                'base_amount' => 100,

            ]);

        $this->fixtures->edit('balance', '10000000000000', [
            'balance' => 10,
        ]);

        $this->startTest();

        $debitNote = $this->getLastEntity('debit_note', true);

        $this->assertArraySelectiveEquals([
            'admin_id'    => 'RzrpySprAdmnId',
            'base_amount' => 300,
        ], $debitNote);

        $disputeDetails = $this->getDbEntities('debit_note_detail',
            [
                'detail_type' => 'dispute',
            ])->toArray();

        $this->assertArraySelectiveEquals([
            [
                'detail_type' => 'dispute',
                'detail_id'   => 'randomDisId123',
            ],
            [
                'detail_type' => 'dispute',
                'detail_id'   => 'randomDisId456',
            ],
        ], $disputeDetails);
    }

    public function testCreateDebitNoteWithDisputeInInvalidStatusShouldFail()
    {
        $this->setUpFixtures(
            [
                'id'              => 'randomDisId123',
                'payment_id'      => 'randomPayId123',
                'internal_status' => 'represented',
                'status'          => 'under_review',
            ],
            'payment:captured',
            [
                'id' => 'randomPayId123',
            ]);


        $this->startTest();
    }

    public function testCreateDuplicateDebitNoteForPaymentShouldFail()
    {
        $this->setUpFixtures(
            [
                'id'              => 'randomDisId123',
                'payment_id'      => 'randomPayId123',
                'internal_status' => 'lost_merchant_not_debited',
                'status'          => 'lost',
            ],
            'payment:captured',
            [
                'id' => 'randomPayId123',
            ]);

        $this->fixtures->create('debit_note_detail', [
            'detail_id'   => 'randomDisId123',
            'detail_type' => 'dispute',
        ]);

        $this->fixtures->edit('balance', '10000000000000', [
            'balance' => 10,
        ]);

        $this->startTest();
    }

    public function testCreateDebitNoteWithSufficientMerchantBalanceShouldFail()
    {
        $this->setUpFixtures(
            [
                'id'              => 'randomDisId123',
                'payment_id'      => 'randomPayId123',
                'internal_status' => 'lost_merchant_not_debited',
                'status'          => 'lost',
                'amount'          => 100,
            ],
            'payment:captured',
            [
                'id'     => 'randomPayId123',
                'amount' => 100,
            ]);


        $this->startTest();
    }

    public function testDebitNoteEntityAdminFetch()
    {
        $input = [
            'id'          => 'debitNoteId123',
            'merchant_id' => '10000000000000',
            'base_amount' => 100,
            'admin_id'    => 'RzpySprAdminId',
        ];

        $this->fixtures->create('debit_note', $input);

        $response = $this->getLastEntity('debit_note', true);

        $this->assertArraySelectiveEquals($input, $response);
    }

    public function testDebitNoteDetailEntityAdminFetch()
    {
        $input = [
            'detail_id'   => 'randomDisId123',
            'detail_type' => 'dispute',
        ];

        $this->fixtures->create('debit_note_detail', $input);

        $response = $this->getLastEntity('debit_note_detail', true);

        $this->assertArraySelectiveEquals($input, $response);
    }

}
