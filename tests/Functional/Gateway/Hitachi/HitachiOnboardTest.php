<?php


namespace RZP\Tests\Functional\Gateway\Hitachi;

use Mockery;
use RZP\Models\Merchant;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Hitachi\TerminalFields;
use RZP\Models\Merchant\Detail;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Models\Merchant\Repository as MerchantRepo;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class HitachiOnboardTest extends TestCase
{
    use RequestResponseFlowTrait;
    use TerminalTrait;

    protected $input;

    protected $gateway = 'hitachi';

    protected $merchantId;

    protected $pgMerchantId;

    protected $terminalsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function getDefaultInput()
    {
        return [
            'gateway'                   => 'hitachi',
            'gateway_input'             => [
                'mcc'                           => '1345',
                'currency_code'                 => 'INR',
                'trans_mode'                    => 'CARDS',
            ],
        ];
    }

    public function testOnboardFailure()
    {
        $this->createMerchants();

        $data =$this->getDefaultInput();

        $merchantId = $this->merchantId;

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceSendRequest(function () {
            return $this->getHitachiOnboardIntegrationErrorResponse();
        }, 1);

        $this->makeRequestAndCatchException(
            function() use ($merchantId, $data)
            {
                $this->onboard($merchantId, $data);
            },
            \RZP\Exception\IntegrationException::class);
    }

    public function testOnboardViaTerminalService()
    {
        $this->createMerchants();

        $data =$this->getDefaultInput();

        // Though in production actual terminal will be created by terminal service, but since that part is mocked,
        // creating the terminal through code only and the mock response shd return that terminal id
        $terminal = $this->fixtures->create('terminal:direct_hitachi_terminal');
        $tid = $terminal->getId();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceSendRequest(function () use ($tid){
            return $this->getHitachiOnboardResponse($tid);
        }, 1);

        $response = $this->onboard($this->merchantId, $data);

        $this->assertNotNull($response);

        $this->assertEquals('hitachi', $response['gateway']);

        $this->assertEquals(['non_recurring', 'recurring_3ds', 'recurring_non_3ds', 'debit_recurring'], $response['type']);
    }

    public function testMerchantDoesntExist()
    {
        $data = $this->getDefaultInput();

        $response = $this->onboard(null, $data);

        $this->assertEquals($response['error']['code'], 'BAD_REQUEST_ERROR');
    }

    public function testGatewayDoesntExist()
    {
        $this->createMerchants();

        $data = $this->getDefaultInput();

        $data['gateway'] = 'rzp';

        $merchant = $this->merchantId;

        $this->makeRequestAndCatchException(
            function() use ($merchant, $data)
            {
                $this->onboard($merchant, $data);
            },
            \RZP\Exception\BadRequestValidationFailureException::class);
    }

    protected function onboard($id, $input)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/merchants/'.$id.'/terminals/onboard',
            'content' => $input
        ];

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function mockServerContentFunction($closure, $gateway = null)
    {
        $server = $this->mockServer('hitachi')
            ->shouldReceive('content')
            ->andReturnUsing($closure)
            ->mock();

        $this->setMockServer($server, $gateway);

        return $server;
    }

    protected function mockServer($gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        $class = $this->app['gateway']->getServerClass($gateway);

        return Mockery::mock($class, [])->makePartial();
    }

    protected function setMockServer($server, $gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        return $this->app['gateway']->setServer($gateway, $server);
    }

    protected function createMerchants()
    {
        $this->pgMerchantId = $this->fixtures->create('merchant')->getId();

        $this->merchantId = $this->fixtures->create('merchant_detail:valid_fields')['merchant_id'];
    }
}
