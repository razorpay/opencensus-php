<?php

namespace Unit\Services;

use Mockery;
use \WpOrg\Requests\Response;
use Tests\Unit\TestCase;
use RZP\Services\TerminalsService;
use RZP\Tests\Functional\Helpers\TerminalTrait;

class TerminalsServiceTest extends TestCase
{
    use TerminalTrait;

    protected $terminalsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->terminalsService = new TerminalsService($this->app);
    }

    public function testfetchMerchantTokenisationOnboardedNetworks()
    {
        $gateways = ['tokenisation_visa', 'tokenisation_mastercard', 'tokenisation_rupay'];

        $terminalsServiceMock = Mockery::mock(TerminalsService::class, [$this->app])->makePartial();

        $terminalsServiceMock->shouldAllowMockingProtectedMethods();

        $this->app->instance('terminals_service', $terminalsServiceMock);

        $terminalsServiceMock->expects('makeRequest')
            ->times(1)
            ->andReturnUsing(function () use ($gateways) {
                $data = [];
                foreach ($gateways as $gateway) {
                    $data[] = ["gateway" => $gateway];
                }
                $response =  new \WpOrg\Requests\Response;
                $responseData = ['data' => $data];
                $response->body = json_encode($responseData);
                $response->status_code = 200;

                return $response;
            });

        // Using $terminalsServiceMock as we cannot use a new instance and mock at the same time
        $response = $terminalsServiceMock->fetchMerchantTokenisationOnboardedNetworks('100002Razorpay');

        $this->assertEquals(['VISA', 'MC', 'RUPAY'], $response);
    }

    public function testfetchMerchantTokenisationOnboardedNetworksForDinersWhichIsNotSupportedYet()
    {
        $gateways = ['tokenisation_diners'];

        $terminalsServiceMock = Mockery::mock(TerminalsService::class, [$this->app])->makePartial();

        $terminalsServiceMock->shouldAllowMockingProtectedMethods();

        $this->app->instance('terminals_service', $terminalsServiceMock);

        $terminalsServiceMock->expects('makeRequest')
            ->times(1)
            ->andReturnUsing(function () use ($gateways) {
                $data = [];
                foreach ($gateways as $gateway) {
                    $data[] = ["gateway" => $gateway];
                }
                $response =  new \WpOrg\Requests\Response;
                $responseData = ['data' => $data];
                $response->body = json_encode($responseData);
                $response->status_code = 200;

                return $response;
            });

        // Using $terminalsServiceMock as we cannot use a new instance and mock at the same time
        $response = $terminalsServiceMock->fetchMerchantTokenisationOnboardedNetworks('100002Razorpay');

        $this->assertEquals([], $response);
    }
}
