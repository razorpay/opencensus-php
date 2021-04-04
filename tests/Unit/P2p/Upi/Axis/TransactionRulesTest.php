<?php

namespace RZP\Tests\Unit\P2p\Upi\Axis;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Models\P2p\Transaction\Rules;
use RZP\Models\P2p\Transaction\Service;
use RZP\Tests\Functional\Partner\Commission\Action;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class TransactionRulesTest extends TestCase
{
    use TransactionTrait;
    /**
     * @var Context
     */
    protected $context;

    protected $action;

    protected $mode = Mode::TEST;

    protected $gateway = 'p2p_upi_axis';

    protected $entity = 'transaction';

    protected $gatewayInput;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function testRules()
    {
        $transaction = $this->createPayIncomingTransaction();

        $rule = new Rules($transaction);

        $rules = $rule->getRules();

        foreach ($rules as $name => $value)
        {
            $this->assertRuleValues($rule, $value);
        }
    }

    protected function assertRuleValues(Rules $rule, $value)
    {
        // First Check for function definition
        if (isset($value['function']) or isset($value['values']))
        {
            // Only Function and Values keys exists
            $this->assertArrayKeysExist($value, ['function', 'values']);

            $this->assertTrue(is_string($value['function']));

            $this->assertRuleValues($rule, $value['values']);

            return;
        }

        // Now check return values which can only be 0 or 1
        if (is_array($value) and (isset($value[0]) or isset($value[1])))
        {
            $this->assertArrayKeysExist($value, [0, 1]);

            $this->assertRuleValues($rule, $value[0]);

            $this->assertRuleValues($rule, $value[1]);

            return;
        }

        if (is_bool($value) or is_string($value))
        {
            return true;
        }

        $this->fail('Invalid rule');
    }
}
