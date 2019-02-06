<?php


namespace RZP\Tests\Functional\Gateway\Hitachi;

use Mockery;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Hitachi\TerminalFields;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class HitachiOnboardTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $input;

    protected $gateway = 'hitachi';

    protected $merchantId;

    protected $pgMerchantId;

    public function setUp()
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

    public function testOnboard()
    {
        $this->createMerchants();

        $data =$this->getDefaultInput();

        $response = $this->onboard($this->merchantId, $data);

        $this->assertNotNull($response);

        $this->assertEquals($response['gateway'], 'hitachi');

        $this->assertEquals($response['type'], ['non_recurring', 'recurring_3ds', 'recurring_non_3ds', 'debit_recurring']);
    }

    public function testOnboardFailure()
    {
        $this->createMerchants();

        $data =$this->getDefaultInput();

        $merchant = $this->merchantId;

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            $content[TerminalFields::RESPONSE_CODE] = '05';
        });

        $this->makeRequestAndCatchException(
            function() use ($merchant, $data)
            {
                $this->onboard($merchant, $data);
            },
            \RZP\Exception\GatewayErrorException::class);
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

    public function testGatewayImplementationDoesntExist()
    {
        $this->createMerchants();

        $data = $this->getDefaultInput();

        $data['gateway'] = 'upi_hulk';

        $merchant = $this->merchantId;

        $this->makeRequestAndCatchException(
            function() use ($merchant, $data)
            {
                $this->onboard($merchant, $data);
            },
            \RZP\Exception\RuntimeException::class);
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
