<?php

namespace RZP\Tests\Unit\P2p\Upi\Axis;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\Functional\Partner\Commission\Action;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class ScenarioTest extends TestCase
{
    public function testScenarioConstants()
    {
        $constants = (new \ReflectionClass(Scenario::class))->getConstants();

        foreach ($constants as $constant => $value)
        {
            $this->assertSame($constant, $value);
        }
    }
}
