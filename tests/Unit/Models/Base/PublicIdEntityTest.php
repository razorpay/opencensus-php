<?php

namespace RZP\Tests\Unit\Models\Base;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\PublicEntity;

class PublicEntityTest extends TestCase
{
    public function testIsPublicEntity()
    {
        $admin = new \RZP\Models\Admin\Admin\Entity;

        $nonPublic = new \RZP\Models\Base\UniqueIdEntity;

        $this->assertTrue(PublicEntity::isPublicEntity($admin));

        $this->assertFalse(PublicEntity::isPublicEntity($nonPublic));
    }
}
