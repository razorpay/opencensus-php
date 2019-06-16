<?php

namespace RZP\Tests\Unit\P2p\Upi\Axis;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Models\P2p\Transaction\Service;
use RZP\Tests\Functional\Partner\Commission\Action;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class TransactionTest extends TestCase
{
    /**
     * @var Context
     */
    protected $context;

    protected $action;

    protected $mode = Mode::TEST;

    protected $gateway = 'p2p_upi_axis';

    protected $entity = 'transaction';

    protected $gatewayInput;

    public function setUp()
    {
        parent::setUp();

        $this->setContext();

        $this->gatewayInput = new ArrayBag();
    }

    public function testAmountValidator()
    {
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
