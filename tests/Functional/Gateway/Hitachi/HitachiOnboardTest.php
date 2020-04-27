<?php


namespace RZP\Tests\Functional\Gateway\Hitachi;

use Mockery;
use RZP\Models\Merchant;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Hitachi\TerminalFields;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Repository as MerchantRepo;
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

    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('mozart');
    }

    public function testOnboardMozart()
    {
        $this->enableRazorXTreatmentForRazorX();
        $this->createMerchants();

        $data =$this->getDefaultInput();

        $response = $this->onboard($this->merchantId, $data);

        $this->assertNotNull($response);

        $this->assertEquals($response['gateway'], 'hitachi');

        $this->assertEquals($response['type'], ['non_recurring', 'recurring_3ds', 'recurring_non_3ds', 'debit_recurring']);
    }

    // Should add default merchant details if not present
    public function testOnboardWhenRequiredMerchantDetailsAreMissing()
    {
        $this->createMerchants();

        $data = $this->getDefaultInput();

        $merchant = (new MerchantRepo)->findOrFailPublic($this->merchantId);

        $merchantDetail = $merchant->merchantDetail;

        $merchantDetail[Detail\Entity::BUSINESS_OPERATION_ADDRESS] =  null;
        $merchantDetail[Detail\Entity::BUSINESS_OPERATION_STATE]   =  "dasf";
        $merchantDetail[Detail\Entity::BUSINESS_OPERATION_PIN]     =  "123";
        $merchantDetail[Detail\Entity::BUSINESS_DBA]               =  "abc";
        $merchantDetail[Detail\Entity::BUSINESS_NAME]              =  "xyz";
        $merchantDetail[Detail\Entity::BUSINESS_OPERATION_CITY]    =  null;
        $merchantDetail->save();

        $response = $this->onboard($this->merchantId, $data);

        $this->assertNotNull($response);

        $this->assertEquals($response['gateway'], 'hitachi');

        $this->assertEquals($response['type'], ['non_recurring', 'recurring_3ds', 'recurring_non_3ds', 'debit_recurring']);
    }

    // this shd fail in validation as name is mandatory
    public function testOnboardWhenRequiredMerchantDetailsAlongWithNameAreMissing()
    {
        $this->createMerchants();

        $data = $this->getDefaultInput();

        $merchant = (new MerchantRepo)->findOrFailPublic($this->merchantId);

        $merchantDetail = $merchant->merchantDetail;

        $merchantDetail[Detail\Entity::BUSINESS_OPERATION_ADDRESS] =  "test Address";
        $merchantDetail[Detail\Entity::BUSINESS_OPERATION_STATE]   =  "KA";
        $merchantDetail[Detail\Entity::BUSINESS_OPERATION_PIN]     =  "123456";
        $merchantDetail[Detail\Entity::BUSINESS_DBA]               =  "abc";
        $merchantDetail[Detail\Entity::BUSINESS_NAME]              =  "";
        $merchantDetail[Detail\Entity::BUSINESS_OPERATION_CITY]    =  "Bengaluru";
        $merchantDetail->save();

        $merchantId = $this->merchantId;

        $this->makeRequestAndCatchException(
            function() use ($merchantId, $data)
            {
                $this->onboard($merchantId, $data);
            },
            \RZP\Exception\BadRequestValidationFailureException::class);
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
