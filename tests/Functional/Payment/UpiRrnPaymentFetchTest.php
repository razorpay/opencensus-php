<?php

namespace Functional\Payment;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Services\UpiPayment;


class UpiRrnPaymentFetchTest extends TestCase
{
    use RequestResponseFlowTrait;

    public $sampleSpltizOutput = [
        "status_code" => 200,
        "response" => [
            "id" => "OhUK4RTNhdS0R3",
            "project_id" => "Nqtt4y5giP28ml",
            "experiment" => [
                "id" => "OkJiGY1kFo8JOg",
                "name" => "UPI RRN search whitelist",
                "exclusion_group_id" => ""
            ],
            "variant" => [
                "id" => "OkJiGYJSO5ZhXv",
                "name" => "vas_rrn_search",
                "variables" => [
                    [
                        "key" => "result",
                        "value" => "upi_rrn_search_enabled"
                    ]
                ],
                "experiment_id" => "OkJiGY1kFo8JOg",
                "weight" => 100
            ],
            "Reason" => "bucketer",
            "steps" => ["sampler", "exclusion", "audience", "assign_bucket"]
        ]
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/UpiRrnPaymentFetchTestData.php';

        parent::setUp();
    }

    public function mockUpsResponse()
    {
        $upsService = $this->getMockBuilder( UpiPayment\Service::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('upi.payments', $upsService);

        $this->app['upi.payments']
              ->method('fetchAuthorizeEntityViaRRN')
              ->willReturn([['payment_id' => 'Ohx4E6GLjW1KDT']]);
    }

    protected function mockSplitzTreatmentBulkRequest($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    public function testFetchUpiPaymentsByRrn()
    {
        $this->ba->proxyAuth();

        $this->fixtures->org->addFeatures(['vas_merchant'], '100000razorpay');

        $this->mockSplitzTreatmentBulkRequest($this->sampleSpltizOutput);

        $this->mockUpsResponse();

        $this->fixtures->create('upi', [
            'payment_id'    => 'Ohx4E6GLjW1KDQ',
            'npci_reference_id' => '422012444250'
        ])['id'];

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDT',
                'created_at'   => 1723021115,
                'reference16' => '422012444250'
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDQ',
                'created_at'   => 1723021115,
                'reference16' => '422012444250'
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDR',
                'created_at'   => 1723021115,
                'reference16' => '422012444251'
            ]);

        $this->startTest();
    }

    public function testFetchUpiPaymentsByRrnPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    // RRN should be ignored if feature flag or experiment is not enabled
    public function testFetchUpiPaymentsByRrnWithoutFeatureFlag()
    {
        $this->ba->proxyAuth();

        $this->mockUpsResponse();

        $this->fixtures->create('upi', [
            'payment_id'    => 'Ohx4E6GLjW1KDQ',
            'npci_reference_id' => '422012444250'
        ])['id'];

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDT',
                'created_at'   => 1723021115,
                'reference16' => '422012444250'
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDQ',
                'created_at'   => 1723021115,
                'reference16' => '422012444250'
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDR',
                'created_at'   => 1723021115,
                'reference16' => '422012444251'
            ]);

       $this->startTest();
    }

    public function testFetchUpiPaymentsByRrnWithAdditionalFilters()
    {
        $this->ba->proxyAuth();

        $this->fixtures->org->addFeatures(['vas_merchant'], '100000razorpay');

        $this->mockSplitzTreatmentBulkRequest($this->sampleSpltizOutput);

        $this->mockUpsResponse();

        $this->fixtures->create('upi', [
            'payment_id'    => 'Ohx4E6GLjW1KDQ',
            'npci_reference_id' => '422012444250',
        ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDT',
                'created_at'   => 1723021115,
                'reference16' => '422012444250',
                'email' => 'test@razorpay.com',
                'status' => 'captured',
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDQ',
                'created_at'   => 1723021115,
                'reference16' => '422012444250',
                'email' => 'test2@razorpay.com',
                'status' => 'captured',
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDR',
                'created_at'   => 1723021115,
                'reference16' => '422012444250',
                'email' => 'test@razorpay.com',
                'status' => 'authorized',
            ]);

        $this->startTest();
    }

}
