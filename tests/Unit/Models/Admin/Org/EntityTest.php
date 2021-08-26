<?php

namespace Unit\Models\Admin\Org;

use RZP\Models\Admin\Org\Entity;
use RZP\Tests\TestCase;

class EntityTest extends TestCase
{

    public function testIsDynamicWalletFlowOrg()
    {
        $result  = Entity::isDynamicWalletFlowOrg(Entity::AXIS_ORG_ID);
        assertTrue($result);
    }
}
